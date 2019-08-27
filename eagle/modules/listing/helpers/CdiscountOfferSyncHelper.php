<?php
namespace eagle\modules\listing\helpers;

use yii;
use yii\base\Exception;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\util\helpers\SQLHelper;

use eagle\modules\html_catcher\helpers\HtmlCatcherHelper;
use eagle\modules\order\models\CdiscountOrderDetail;
use eagle\modules\catalog\helpers\PhotoHelper;
use eagle\modules\catalog\models\Photo;
use eagle\models\catalog\Product;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\order\models\OdOrderItem;
use yii\data\Pagination;
use eagle\modules\listing\models\CdiscountOfferTerminator;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\listing\models\HotsaleProduct;
use eagle\modules\listing\models\FollowedProduct;

class CdiscountOfferSyncHelper{

	/**
	 +---------------------------------------------------------------------------------------------
	 *
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function changeDBPuid($puid){
		if ( empty($puid))
			return false;
	
		return true;
	}//end of changeDBPuid
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取CD offers
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$SellerProductIdList
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/07/20			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GetOfferList($cdiscountAccount, $SellerProductIdList=[]){
		$timeout=600; //s
		$retInfo=[];
		echo "\n enter function : GetOfferList";
	
		$cdiscount_token = $cdiscountAccount['token'];

		$config = array('tokenid' => $cdiscount_token);
		$get_param['config'] = json_encode($config);

		$params=[];
		if(!empty($SellerProductIdList)){
			$params['SellerProductIdList'] = $SellerProductIdList;
			$get_param['query_params'] = json_encode($params);
		}

		$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("GetOfferList",$get_param,$post_params=array(),$timeout );
	
		return $retInfo;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取CD商品
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $CategoryCode
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/07/20			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronGetProductList($CategoryCode){
		$timeout=240; //s
		$retInfo=[];
		echo "\n enter function : cronGetProductList";
	
		$SAASCDISCOUNTUSERLIST = SaasCdiscountUser::find()->where("is_active='1' ")->orderBy("last_order_success_retrieve_time asc")->all();
			
		//retrieve orders  by  each cdiscount account
		foreach($SAASCDISCOUNTUSERLIST as $cdiscountAccount ){
			$cdiscount_token = $cdiscountAccount['token'];
				
			$config = array('tokenid' => $cdiscount_token);
			$get_param['config'] = json_encode($config);
				
			if(!empty($CategoryCode)){
				$params['CategoryCode'] = $CategoryCode;
				$get_param['query_params'] = json_encode($params);
			}
				
			$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("GetProductList",$get_param,$post_params=array(),$timeout );
			echo "\n ProductList return: \n";
			print_r($retInfo);
		}
	
		return $retInfo;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台 分页获取CD offers
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/05/10			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronGetOfferListPaginated(){
		//一次run五个账号，避免timeout
		$halfMonthAgo = date("Y-m-d H:i:s" ,time()-3600*24*15);
		$towDaysAgo = date("Y-m-d H:i:s" ,time()-3600*24*2);
		$accounts = SaasCdiscountUser::find()
			->where("`fetcht_offer_list_time` is null or `fetcht_offer_list_time`='0000-00-00 00:00:00' or `fetcht_offer_list_time`<'$halfMonthAgo' ")
			->andWhere("`token_expired_date`>'$towDaysAgo'")
			->andWhere(['is_active'=>1])->orderBy("fetcht_offer_list_time ASC")->limit(5)->all();
		echo "\n start to cronGetOfferListPaginated at ".TimeUtil::getNow().', run '.count($accounts).' accounts this time. \n';
		try {
			foreach($accounts as $account){
				$shop = self::getSellerInfo($account->token);
				//var_dump($shop);
				if(isset($shop['proxyResponse']['success']) && isset($shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName']))
					$account->shopname = $shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName'];
				
				echo "\n start to getOfferListPaginated for uid:".$account->uid.";";
				$rtn = self::getOfferListPaginated($account->uid,$account->username);
				echo "\n getOfferListPaginated's return: \n".print_r($rtn,true)."\n";
				if($rtn['success']){
					$account->fetcht_offer_list_time = TimeUtil::getNow();
					$account->product_retrieve_message = '';
					$account->save();
				}else{
					$account->product_retrieve_message = '';
					$account->save();
				}
			}
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 分页获取CD商品列表 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @params 	$uid		用户
	 * @params 	$seller		卖家店铺
	 * @params 	$params		过滤参数
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/04/04			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOfferListPaginated($uid,$seller='',$params=[]){
		$timeout=300; //s
		$result['success']=true;
		$result['message'] = '';
		echo "\n enter function : getOfferListPaginated";
		
		$query = SaasCdiscountUser::find()->where(['uid'=>$uid,'is_active'=>1]);
		if(!empty($seller))
			$query->andWhere(['username'=>$seller]);
		$SAASCDISCOUNTUSERLIST=$query->all();
		
		//get offer list by each cdiscount account
		foreach($SAASCDISCOUNTUSERLIST as $cdiscountAccount ){
			$cdiscount_token = $cdiscountAccount->token;
			
			$config = ['tokenid'=>$cdiscount_token];
			$get_param['config'] = json_encode($config);
			$query_params=[];
			if(!empty($params)){
				foreach ($params as $k=>$v)
					$query_params[$k] = $v;
			}
			
			//$rtn['offers']=[];
			$rtn['message']='';
			$rtn['success']=true;
			$CurrentPageNumber=1;//当前获取页
			$NumberOfPages='';//总页数
			$NextPageNumber=1;//下次调用的页码
			//$cdiscountAccount->last_product_retrieve_time = TimeUtil::getNow();
			$cdiscountAccount->save();
			do{
				echo "\n get offer list page: $NextPageNumber";
				$insert_rtn =[];
				$query_params['PageNumber'] = $NextPageNumber;
				$get_param['query_params'] = json_encode($query_params);
				$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("GetOfferListPaginated",$get_param,$post_params=array(),$timeout );
				//var_dump($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OperationSuccess']);
				//var_dump($retInfo['message']);
				//var_dump($retInfo['success']);
				if(!empty($retInfo['url'])) echo "\n ".$retInfo['url'];
				
				//echo "\n ".count($retInfo['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OperationSuccess']['OfferList']['Offer']);
				if(!empty($retInfo['success']) && 
					!empty($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OperationSuccess'])
				){//获取offer list成功
					//var_dump($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OfferList']);
					if(!empty($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OfferList'])){
						$pageOfferList = $retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OfferList'];
						if(isset($pageOfferList['SellerProductId'])){
							$tmpOffer[] = $pageOfferList;
							$pageOfferList = $tmpOffer;
						}elseif(isset($pageOfferList['Offer'])){
							$pageOfferList = $pageOfferList['Offer'];
						}
						echo "\n api get ".count($pageOfferList)." offers; \n";
						echo "\n start to insert this page offers into offer_list :";
						$insert_rtn = self::_InsertCdiscountOffer($pageOfferList,$cdiscountAccount);
						if(empty($insert_rtn['success'])){
							echo "\n ".$insert_rtn['message'];
							$rtn['message'] .= $insert_rtn['message'];
							//continue;
						}
						if(empty($insert_rtn['success']))
							$rtn['success']=false;
					}
					if(isset($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['CurrentPageNumber'])){
						$CurrentPageNumber = (int)$retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['CurrentPageNumber'];
						$NextPageNumber = $CurrentPageNumber+1;
					}
					if(isset($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['NumberOfPages'])){
						if(empty($NumberOfPages)) $NumberOfPages = (int)$retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['NumberOfPages'];
					}
					echo "\n successed get page: $CurrentPageNumber / $NumberOfPages";
					if(!empty($insert_rtn['message']))
						echo "; _InsertCdiscountOffer return message: ".$insert_rtn['message'];
				}else{//获取失败
					$rtn['success']=false;
					if(isset($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OperationSuccess']) && empty($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OperationSuccess'])){
						if(!empty($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['ErrorMessage']))
							$rtn['message']=$retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['ErrorMessage'];
						else 
							$rtn['message'] = 'get offer list failed!';
					}
					elseif(!empty($retInfo['proxyResponse']['productlist']['s_Body']['s_Fault']['faultstring']) && empty($retInfo['proxyResponse']['productlist']['s_Body']['GetOfferListPaginatedResponse']['GetOfferListPaginatedResult']['OperationSuccess'])){
						$rtn['message'] = $retInfo['proxyResponse']['productlist']['s_Body']['s_Fault']['faultstring'];
					}
					elseif(!empty($retInfo['message']) && empty($retInfo['success'])){
						$rtn['message'] = $retInfo['message'];
					}
					break;
				}
				echo "\n NumberOfPages=".var_dump($NumberOfPages);
				echo "\n CurrentPageNumber=".var_dump($CurrentPageNumber);
				echo "\n NextPageNumber=".var_dump($NextPageNumber);
			}while ($NextPageNumber<=$NumberOfPages /*&& empty($rtn['message'])*/ );//end of call api to get offer list
			echo "\n  get offer list end \n";
			//print_r($retInfo);
			
			if(!empty($rtn['success'])){
				$cdiscountAccount->last_product_success_retrieve_time = time()-3600*24*6;//设置为6日前，让跟卖终结者可以尽快进行一次查询
				$cdiscountAccount->last_product_retrieve_time = TimeUtil::getNow();
				$cdiscountAccount->save();
			}else{
				echo "\n ".$rtn['message'];
				$result['success'] = false;
				$result['message'] .= $rtn['message'];
			}
			
		}//end of each account
	
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取店铺offer listing
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/25			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronGetOfferList(){
		echo "\n Enter function cronGetOfferList() \n";//liang test
		$day_ago = date('Y-m-d H:i:s',strtotime('-1 days'));
		try {
			$SAASCDISCOUNTUSERLIST = SaasCdiscountUser::find()
				->where(["is_active"=>1])
			 
				->andWhere(" (initial_fetched_changed_order_since is null ) OR 
						( last_product_success_retrieve_time  is null 
						  OR last_product_success_retrieve_time < '$day_ago' ) ")
				->all();
			echo "\n SAASCDISCOUNTUSERLIST :count=".count($SAASCDISCOUNTUSERLIST)."; \n";//liang test
			//print_r($SAASCDISCOUNTUSERLIST);
			//retrieve orders  by  each cdiscount account
			foreach($SAASCDISCOUNTUSERLIST as $cdiscountAccount ){
				$uid = $cdiscountAccount->uid;
				echo "\n cdiscountAccount->uid :$cdiscountAccount->uid \n";//liang test
				//if token expired ,wait for syn order and reset token
				if($cdiscountAccount['token_expired_date'] < $day_ago )
					continue;
				
				//update cd account's shop name
				$shop = self::getSellerInfo($cdiscountAccount->token);
				//var_dump($shop);
				if(isset($shop['proxyResponse']['success']) && isset($shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName']))
					$cdiscountAccount->shopname = $shop['proxyResponse']['GetSellerInformation']['GetSellerInformationResult']['Seller']['ShopName'];
						
				echo "\n <br>YS1 start to cronGetOfferList, uid=$uid ... ";
				//异常情况
				if (empty($uid)){
					$message = "site id :".$cdiscountAccount['site_id']." uid:0";
					echo "\n $message";
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					return false;
				}
	
				$updateTime = TimeUtil::getNow();
				//update this cdiscount account as last product retrieve time
				$cdiscountAccount->last_product_retrieve_time = $updateTime;
	
				//$sinceTimeUTC = date("Y-m-d\TH:i:s" ,strtotime($updateTime)-3600*24*30);//UTC time is -8 hours
				//$sinceTimeUTC = substr($sinceTimeUTC,0,10);
				//$dateSince = date("Y-m-d\TH:i:s" ,strtotime($updateTime)-3600*24*30);// test 8hours //1个月前
	
				//start to get offerList
				echo "\n".$updateTime." start to get $uid offerList order for ".$cdiscountAccount['store_name']." \n"; //ystest
	
				$offerList = self::GetOfferList($cdiscountAccount,[]); //get office list from cdiscount api
	
				if (empty($offerList['success'])){
					echo "fail to connect proxy  :".$offerList['message'];
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$offerList['message']],"edb\global");
					$cdiscountAccount->save();
					continue;
				}
	
				echo "\n".TimeUtil::getNow()." got results, start to insert offer to db \n"; //ystest
				//accroding to api respone  , update the last retrieve product time and last retrieve product success time
				if(!empty($offerList['proxyResponse']['OfferList']['GetOfferListResult']['ErrorMessage'])){
					echo "\n".$offerList['proxyResponse']['OfferList']['GetOfferListResult']['ErrorMessage'];
					continue;
				}
				if (!empty ($offerList['proxyResponse']['success'])){
					//sync cdiscount info to cdiscount offer table
					if(!empty($offerList['proxyResponse']['OfferList']['GetOfferListResult']['OfferList']['Offer']) and
					   !empty($offerList['proxyResponse']['OfferList']['GetOfferListResult']['OperationSuccess']) )
					{
						$rtn=self::_InsertCdiscountOffer($offerList['proxyResponse']['OfferList']['GetOfferListResult']['OfferList']['Offer'] , $cdiscountAccount);
						echo "\n uid = $uid handled offers count ".count($offerList['proxyResponse']['OfferList']['GetOfferListResult']['OfferList']['Offer'])." for ".$cdiscountAccount['token'];
						
						//\Yii::info(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid handled offers count ".count($offerList['proxyResponse']['OfferList']['GetOfferListResult']['OfferList']['Offer'])." for ".$cdiscountAccount['token']],"edb\global");
						//update last order success retrieve time of this cdiscount account
						if($rtn['success']){
							$cdiscountAccount->last_product_success_retrieve_time = $updateTime;
						}else{
							echo "\n".$rtn['message'];
						}
					}//end of GetOrderListResult empty or not
				}else{
					if (!empty ($offerList['proxyResponse']['message'])){
						echo "\n uid = $uid proxy error  :".$offerList['proxyResponse']['message'];
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  :".$offerList['proxyResponse']['message'] ],"edb\global");
					}else{
						echo "\n uid = $uid proxy error  : not any respone message";
						//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid = $uid proxy error  : not any respone message"],"edb\global");
					}
					break;
				}
	
				//end of getting orders from cdiscount server
				if (!empty ($offerList['proxyResponse']['message'])){
					$cdiscountAccount->product_retrieve_message = $offerList['proxyResponse']['message'];
				}else
					$cdiscountAccount->product_retrieve_message = '';//to clear the error msg if last attemption got issue
							
				if (!$cdiscountAccount->save()){
					echo "\n failure to save cdiscount operation info \n";
					echo "\n error:".print_r($cdiscountAccount->getErrors(),true);
					//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"failure to save cdiscount operation info ,uid:".$cdiscountAccount['uid']."error:". print_r($cdiscountAccount->getErrors(),true)],"edb\global");
					break;
				}else{
					echo "\n CdiscountAccount model save !";
				}
	
			}//end of each cdiscount user account
		}
		catch (Exception $e) {
			echo "Background retrieve offer list :".$e->getMessage();
			//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',"uid retrieve offer list :".$e->getMessage()],"edb\global");
		}
	}
	
	
	/**
	
	*/
	private static function _InsertCdiscountOffer($OfferList , $cdiscountAccount){
		
		echo "\n enter _InsertCdiscountOffer";
		$rtn['success']=true;
		$rtn['message']='';
		if(empty($OfferList)){
			echo "\n 0 offer to InsertCdiscountOffer;\n ";
			return $rtn;
		}
		$columnMapping = [
			"BestShippingCharges"=>"best_shipping_charges",
			"Comments"=>"comments",
			"CreationDate"=>"creation_date",
			"DeaTax"=>"dea_tax",
			"DiscountList"=>"discount_list",
			"EcoTax"=>"eco_tax",
			"IntegrationPrice"=>"integration_price",
			"LastUpdateDate"=>"last_update_date",
			"MinimumPriceForPriceAlignment"=>"minimum_price_for_price_alignment",
			"OfferBenchMark"=>"offer_bench_mark",
			"OfferPoolList"=>"offer_pool_list",
			"OfferState"=>"offer_state",
			"ParentProductId"=>"parent_product_id",
			"Price"=>"price",
			"PriceMustBeAligned"=>"price_must_be_aligned",
			"ProductCondition"=>"product_condition",
			"ProductEan"=>"product_ean",
			"ProductId"=>"product_id",
			"ProductPackagingUnit"=>"product_packaging_unit",
			"ProductPackagingUnitPrice"=>"product_packaging_unit_price",
			"ProductPackagingValue"=>"product_packaging_value",
			"SellerProductId"=>"seller_product_id",
			"ShippingInformationList"=>"shipping_information_list",
			"Stock"=>"stock",
			"StrikedPrice"=>"striked_price",
			"VatRate"=>"vat_rate",
		];
		//读取已存在的offer部分信息到内存
		$t1 = time();//liang test
		echo "\n ready to check existing offers infos :";//liang test
		try{
			$existingOffers = CdiscountOfferList::find()->select("`last_update_date`,`product_id`,`seller_product_id`")->where(['seller_id'=>$cdiscountAccount['username']])->asArray()->all();
			$existingOffersOldInfos = [];
			foreach ($existingOffers as $e_offer){
				$existingOffersOldInfos[$e_offer['product_id']] = $e_offer;
			}
		}catch (\Exception $e) {
			echo "\n".$e->getMessage();
		}
		$t1_t2 = time()-$t1;//liang test
		$t2 = time();//liang test
		echo "\n read existing offers infos to catch used ".$t1_t2."second...";//liang test
		
		//$tempCdiscountOfferModel = new CdiscountOfferList();
		//$CdiscountOfferModelAttr = $tempCdiscountOfferModel->getAttributes();
		try{
			$insertData = [];
			$updateRecord = 0;
			$insertRecord = 0;
			$ignore = 0;
			$failure = 0;
			$changeProd = [];
			$insertIdList=[];
			foreach ($OfferList as $offer){
				$un_existing = false;//是否为非offer_list已经存在的
				if( !empty($offer['ProductId']) && !empty($offer['SellerProductId'])){
					//与内存中的$existingOffersOldInfos比较，如果last_update_date有变化，则插入/更新。
					if(isset($existingOffersOldInfos[$offer['ProductId']])){//已存在的offer
						if($offer['LastUpdateDate']==$existingOffersOldInfos[$offer['ProductId']]['last_update_date'] 
							&& $offer['OfferState']==$existingOffersOldInfos[$offer['ProductId']]['offer_state']) {
							//对比上次无更新,且状态无更新
							echo "\n ".$offer['ProductId']." no change, ignore update;";
							$ignore++;
							continue;
						}
					}else{
						//new offer
						echo "\n ".$offer['ProductId']." is a new record;";
						$un_existing = true;
					}
					
					$tmpData = [];
					foreach ($offer as $key=>$value){
						if(isset($columnMapping[$key])){
							if(is_array($value)){
								$tmpData[$columnMapping[$key]] = json_encode($value);
							}else{
								$tmpData[$columnMapping[$key]] = $value;
							}
							//无效值处理
							if($tmpData[$columnMapping[$key]]=='{"@attributes":{"i_nil":"true"}}' || $tmpData[$columnMapping[$key]]=='[]'){
								$tmpData[$columnMapping[$key]]='';
							}
							
							if(in_array($columnMapping[$key],['best_shipping_charges','dea_tax','eco_tax','integration_price','minimum_price_for_price_alignment','price','product_packaging_unit_price','product_packaging_value','stock','striked_price','vat_rate'])){
								//对数值类型的column做处理,排除无效值
								if(stripos($tmpData[$columnMapping[$key]],'"i_nil":"true"')!==false || !is_numeric($tmpData[$columnMapping[$key]]))
									$tmpData[$columnMapping[$key]]=0;
								else 
									$tmpData[$columnMapping[$key]]=floatval($tmpData[$columnMapping[$key]]);
							}
						}
					}//end of getting value from offer
					
					if(strtolower($offer['OfferState'])!=='active' && stripos($offer['OfferState'],'"i_nil":"true"')==false){
						$tmpData['is_bestseller'] = '-';
						$tmpData['bestseller_name'] = '';
					}else{
						if ($un_existing==true){
							$tmpData['is_bestseller'] = '';
							$tmpData['bestseller_name'] = '';
						}
					}
					
					//save parent
					if(!empty($tmpData['parent_product_id']) && $tmpData['parent_product_id']!=='{"@attributes":{"i_nil":"true"}}'){
						$parentModel = CdiscountOfferList::find()->where(['product_id'=>$tmpData['parent_product_id']])->andWhere("parent_product_id is null OR parent_product_id = ''")->one();
						if($parentModel==null){
							echo "\n productid:".$offer['ProductId']." is a variant child,insert father: ".$tmpData['parent_product_id']." info";
							$parentModel = new CdiscountOfferList();
							$parentModel->product_id = $tmpData['parent_product_id'];
							$parentModel->seller_product_id = '';
							$parentModel->sku = '';
							$parentModel->seller_id = $cdiscountAccount['username'];
							$parentModel->concerned_status = 'I';//拉单新建的offer记录自动默认为忽略，不查询
							$parentModel->save(false);
						}
						$t2a = time();//liang test
						$syncRtn = CdiscountOfferSyncHelper::syncProdInfoWhenGetOrderDetail($cdiscountAccount['uid'],$tmpData['parent_product_id'],$priority=3,$cdiscountAccount['username']);	
						if(empty($syncRtn['success'])){
							echo "\n Erroe!!! syncProdInfoWhenGetOrderDetail error:".print_r($syncRtn,true);
						}
						$t2b_t2a = time()-$t2a;//liang test
						echo "\n sync ProdInfo when is new record used :".$t2b_t2a."second...";//liang test
					}
					
					//hardcode a product_url
					$tmpData['product_url'] = 'http://www.cdiscount.com/informatique/f-1-'.$offer['ProductId'].'.html';
					
					//if($offer['SellerProductId'] =='AUC0611029354847'){
						echo "\n un_existing: \n";
						var_dump($un_existing);
					//}
					
					if($un_existing==false){
						$model = CdiscountOfferList::find()->where(['product_id'=>$offer['ProductId'],'seller_id'=>$cdiscountAccount['username']])->One();
						if (!empty($model)) {
							echo "\n ".$offer['ProductId']." existing record id:".$model->id.";";
							//update model data
							$tmpData['is_bestseller'] = $model->is_bestseller;
							$tmpData['bestseller_name'] = $model->bestseller_name;
							
							$model->attributes = $tmpData;
							/*
							if(empty($model->seller_id)){
								$model->seller_id = $cdiscountAccount['username'];
							}
							*/
							if(!$model->save()){
								$failure++;
								$rtn['success']=false;
								$rtn['message'].="\n ".$offer['SellerProductId']." failure to save to db :".print_r($model->getErrors());
							}else{
								echo "\n ".$offer['ProductId']." model updated; updateRecord++ ";
								$updateRecord ++;
								//$changeProd[] = $offer['ProductId'];	//update情况下不立即插入html catcher队列
							}
						}else {
							$failure++;
							$rtn['success']=false;
							$rtn['message'].="\n ".$offer['SellerProductId']." failure to find existing record, update skip";
							echo "\n ".$offer['SellerProductId']." failure to find existing record, update skip";
							continue;
						}
					}
					else{
						//push temData to array,batch insert later
						if(!in_array($offer['ProductId'],$insertIdList)){
							$insertIdList[]=$offer['ProductId'];
							$tmpData['seller_id'] = $cdiscountAccount['username'];
							$tmpData['concerned_status'] = "I";//新拉取商品默认为忽略
							$insertData[] = $tmpData;
							$changeProd[] = $offer['ProductId'];
							echo "\n ".$offer['ProductId']." model put into group insert data; insertRecord++ ";
							$insertRecord ++;
						}
					}
					unset($model);
				}else{
					echo "\n empty offer['ProductId'] && empty offer['SellerProductId'], ignore ++";
					$ignore++;
					continue;
				}
			}
			$t2c_t2 = time()-$t2;//liang test
			echo "\n foreach done,used:".$t2c_t2."second...";//liang test
			$t2c1 = time();//liang test
			$insertRecord = SQLHelper::groupInsertToDb('cdiscount_offer_list', $insertData);
			echo "\n groupInsertToDb done,used:".(time()-$t2c1)."second...";//liang test
			/* 耗时过长，暂时屏蔽
			$t2c2 = time();//liang test
			//周期同步offer时，同时同步不存在于api返回的offer里面的 ，没有img的在表offer(类似于跟卖产品)
			$noImgOffers = CdiscountOfferList::find()->select("product_id")->where(['img'=>null])->asArray()->All();
			foreach ($noImgOffers as $noImg){
				if (!empty($noImg['product_id']) && !in_array($noImg['product_id'],$changeProd) ) {
					$changeProd[] = $noImg['product_id'];
				}
			}
			echo "\n get noImgOffers infos done,used:".(time()-$t2c2)."second...";//liang test
			*/
			$t2d = time();//liang test
			//call web capturer api if has prod need addinfo from web
			
			if(!empty($changeProd)){
				$uid = $cdiscountAccount['uid'];
				$field_list=array('product_id','img','title','description','brand');
				$site = '';
				$callback = 'eagle\modules\listing\helpers\CdiscountOfferSyncHelper::webSiteInfoToDb($puid,$prodcutInfo,1,$seller);';
				
				$r = HtmlCatcherHelper::requestCatchHtml($uid,$changeProd,'cdiscount',$field_list,$site,$callback,false,$priority=5,['seller'=>$cdiscountAccount['username']]);
				if(isset($r['success']) && $r['success']==false)
					$rtn['message'] .= "<br>".$r['message'];
				else{
					foreach ($r as $key=>$result){
						if($key=='success')
							continue;
						if(empty($result['success']) && !empty($result['message']))
							$rtn['message'] .= "<br>".$result['message'];
					}
				}
			}
			
			echo "\n requestCatchHtml total used :".(time()-$t2d)."second...";
			echo "data write into db done. ignore  record $ignore, update record:$updateRecord, insert record:$insertRecord .";
			$rtn['updated']=$updateRecord;
			$rtn['inserted']=$insertRecord;
			$rtn['ignored']=$ignore;
			$rtn['failed']=$failure;
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		echo "\n total used :".(time()-$t2)."second...";//liang test
		unset($existingOffers);
		unset($existingOffersOldInfos);
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 跟卖终结者获取到offer信息后更新到offer表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @params 	$uid			用户
	 * @params 	$prodcutInfo	卖家店铺
	 * @params 	$is_base64		过滤参数
	 +---------------------------------------------------------------------------------------------
	 * @author		lzhl		2016/04/04			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function TerminatorInfoToDb($uid,$prodcutInfo, $is_base64=true,$seller=''){
		$rtn = self::webSiteInfoToDb($uid, $prodcutInfo,$is_base64,$seller);
		
		/*
		if($rtn['success']){
			echo "\n save offer info to db successed, start to save terminator history \n";
			$rtn = self::updateTerminatorHistory($uid,$prodcutInfo,$is_base64=true);
			if($rtn['success']){
				echo "\n update terminator history successed \n";
			}else{
				echo "\n update terminator history failed, error: ".$rtn['message']."\n";
			}
		}else{
			echo "\n save offer info to db failed, error: ".$rtn['message']."\n";
		}
		*/
		return true;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * HtmlCatcher获取到offer信息后更新到offer表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @params 	$uid			用户
	 * @params 	$prodcutInfo	卖家店铺
	 * @params 	$is_base64		过滤参数
	 +---------------------------------------------------------------------------------------------
	 * @author		lzhl		2016/04/04			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function webSiteInfoToDb($uid,$prodcutInfo, $is_base64=true,$seller=''){
		echo "\n <br> start to webSiteInfoToDb, uid=$uid ... ";
		$rtn['success']=true;
		$rtn['message']='';
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
		
	 
		try{
			//异常情况
			if (empty($uid)){
				$message = "uid is empty!";
				echo "\n $message";
				//\Yii::error(['cdiscount',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
				return false;
			}
			
			//check  $prodcutInfo should decode or not 
			if (is_string($prodcutInfo) && $is_base64){
				//if string then base64 decode 
				$prodcutInfo = base64_decode($prodcutInfo);
				//if string then json decode
				if (is_string($prodcutInfo)){
					$prodcutInfo = json_decode($prodcutInfo,true);
				}
			}
			
			$current_time=explode(" ",microtime());
			$time2=round($current_time[0]*1000+$current_time[1]*1000);
			$run_time = $time2 - $time1; //这个得到的$time是以 ms 为单位的
			//echo "finished for step 1,use time $run_time ms \n";
			
			//variant(变体)商品特殊处理
			if(!empty($prodcutInfo['product_id'])){
				if(preg_match('/\-/', $prodcutInfo['product_id'])){//is a variant child
					$productIdStr = explode('-', $prodcutInfo['product_id']);
					if(!empty($productIdStr[0])){
						$parent_prod_id = $productIdStr[0];
						if(empty($prodcutInfo['img'])){
							//如果是一个子产品并且已经获得父产品信息，hc_collect_request_queue表中应该已经复制到父信息
							//如果除了product_id外没有其他信息，则表示复制步骤出问题
							//这时候则复制offer_list表的福商品信息，但比较不全面
							$parent_offer = CdiscountOfferList::find()->where(['product_id'=>$parent_prod_id])->andWhere("parent_product_id is null")->one();
							if($parent_offer<>null){
								$prodcutInfo['title'] = $parent_offer->name;
								$prodcutInfo['img'] = json_decode($parent_offer->img,true);
								$prodcutInfo['description'] = $parent_offer->description;
								$prodcutInfo['brand'] = $parent_offer->brand;
								$prodcutInfo['bestseller_name'] = $parent_offer->bestseller_name;
								$prodcutInfo['bestseller_price'] = $parent_offer->bestseller_price;
								$prodcutInfo['product_url'] = $parent_offer->product_url;
							}
						}
					}
				}
			}else {
				$rtn['success']=false;
				$rtn['message']='product_id lost';
				echo "\n product_id lost;";
				return $rtn;
			}
			if(empty($prodcutInfo['product_url']))
				$prodcutInfo['product_url'] = 'http://www.cdiscount.com/informatique/f-1-'.$prodcutInfo['product_id'].'.html';
			//var_dump($listing);
			//echo "got puid $uid and decoded:".print_r($prodcutInfo) ."\n";
			
			//echo "<br> start to get listing by product_id:".print_r($prodcutInfo,true)."<br>";
			if(preg_match('/\-/', $prodcutInfo['product_id'])){//is a variant child{
				$listing_query = CdiscountOfferList::find()->where(" product_id='".$prodcutInfo['product_id']."' or sku='".$prodcutInfo['product_id']."' ")->andWhere(" parent_product_id is not null ");
			}else 
				$listing_query = CdiscountOfferList::find()->where(['product_id'=>$prodcutInfo['product_id'] ])->andWhere(" parent_product_id is null or parent_product_id='' or parent_product_id like '%{\"i_nil\":\"true\"}%'");
			/*现有机制下，同一个用户短时间内只会对一个product_id做一次查询，不论seller_id。因此暂时屏蔽此段
			if(!empty($seller))
				$listing_query->andWhere(['seller_id'=>$seller]);
			*/
			$listings = $listing_query->all();
			
			$current_time=explode(" ",microtime());
			$time3=round($current_time[0]*1000+$current_time[1]*1000);
			$run_time = $time3 - $time2; //这个得到的$time是以 ms 为单位的
			//echo "finished for step 2,use time $run_time ms \n";
			
			//update order details product names
			if(!empty($prodcutInfo['title'])){
				//echo "<br>update cd order detail<br>name=".$prodcutInfo['title']."<br>:productid=".$prodcutInfo['product_id'];
				CdiscountOrderDetail::updateAll(['name'=>$prodcutInfo['title']],'sku=:productid',[':productid'=>$prodcutInfo['product_id']]);	
				//echo "<br>update eagle2 order detail<br>";
				OdOrderItem::updateAll(['product_name'=>$prodcutInfo['title'],'product_url'=>$prodcutInfo['product_url']],"source_item_id=:source_item_id and (product_url='' or product_url is null)",[':source_item_id'=>$prodcutInfo['product_id']]);
				// ,'photo_primary'=>$prodcutInfo['img'][0]
				if(!empty($prodcutInfo['img']) && !empty($prodcutInfo['img'][0])){
				    OdOrderItem::updateAll(['photo_primary'=>$prodcutInfo['img'][0]],"source_item_id=:source_item_id and (photo_primary='' or photo_primary is null)",[':source_item_id'=>$prodcutInfo['product_id']]);
				}
			}
			
			if(empty($seller))
				$yourSellerAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->all();
			else
				$yourSellerAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid,'username'=>$seller])->all();
			$yourSellerNames = [];
			foreach ($yourSellerAccounts as $account){
				$yourSellerNames[]=$account->shopname;
			}
			//定制用户通过excel导入的话可能没有绑定的，需要通过redis补全
			$customizationUsers = CdiscountOfferTerminatorHelper::$customizationUsers;
			if(in_array($uid, $customizationUsers)){
				$customizationUserShops = RedisHelper::RedisGet("CDOT_CustomizationUserShops","user_$uid");
				$customizationUserShops = empty($customizationUserShops)?[]:json_decode($customizationUserShops,true);
				foreach ($customizationUserShops as $shop_name){
					if(!in_array($shop_name,$yourSellerNames))
						$yourSellerNames[] = $shop_name;
				}
			}
			foreach ($listings as $listing){
				if($listing<>null){
					/*product type:
					 * 	c:variant child
					 * 	p:variant parent
					 * 	s:simple
					 */
					$product_type = 's';
					if(!empty($listing->parent_product_id))
						$product_type = 'c';
					if(empty($listing->parent_product_id) && empty($listing->seller_product_id))
						$product_type = 'p';
					
					if($product_type=='s' || $product_type=='c'){
						$listing->sku = $listing->product_id;
					
						$sku = $listing->seller_product_id;
					
						$photo_primary='';
						$photo_others=[];
						if(!empty($prodcutInfo['img'])){
							if(is_array($prodcutInfo['img'])){
								$listing->img = json_encode($prodcutInfo['img']);
								$photo_primary = $prodcutInfo['img'][0];
								$photos = $prodcutInfo['img'];
								unset($photos[0]);
								$photo_others = $photos;
							}
							else{
								$listing->img = $prodcutInfo['img'];
								$photo_primary = $prodcutInfo['img'];
							}
						}
						//update photo info when product photo is null
						if(!empty($sku) && !empty($photo_primary)){
							//七牛缓存
							ImageCacherHelper::getImageCacheUrl($photo_primary,$uid,3);
							
							$rootSku = ProductHelper::getRootSkuByAlias($sku);
							if(!empty($rootSku))
								$sku = $rootSku;
							//echo "\n sku=".$sku."\n";//liang test
							$pd_product = Product::find()->where(['sku'=>$sku])->one();
							//echo "\n this sku=$sku has ".count($pd_product)." products \n";//liang test
							if($pd_product<>null){
								if(empty($pd_product->photo_primary) || stripos($pd_product->photo_primary,'batchImagesUploader/no-img.png')!==false){
									$pd_product->photo_primary =$photo_primary;
								}
								if($pd_product->name=='' or $pd_product->name==$pd_product->sku){
									if(!empty($prodcutInfo['title']))
										$pd_product->name = $prodcutInfo['title'];
								}
								$pd_product->save(false);
								$pd_photos = Photo::find()->where(['sku'=>$sku])->all();
								//echo "\n this sku=$sku has ".count($pd_photos)." photos \n";//liang test
								if($pd_photos==null){
									PhotoHelper::savePhotoByUrl($sku, $photo_primary, $photo_others);
								}
							}
						}
					}else{
						if(is_array($prodcutInfo['img']))
							$listing->img = json_encode($prodcutInfo['img']);
						else
							$listing->img = $prodcutInfo['img'];
					}
					$listing->name = !empty($prodcutInfo['title'])?$prodcutInfo['title']:'';
					$listing->product_url = !empty($prodcutInfo['product_url'])?$prodcutInfo['product_url']:'';
					$listing->description =  !empty($prodcutInfo['description'])?$prodcutInfo['description']:'';
					$listing->brand =  !empty($prodcutInfo['brand'])?$prodcutInfo['brand']:'';
					if(empty($listing->seller_id))
						$listing->seller_id ='N/A';
					
					if(!empty($prodcutInfo['bestseller_name'])){
						$listing->bestseller_name = $prodcutInfo['bestseller_name'];
						//判断用户辖下是否有店铺获得bestseller
						if(in_array($prodcutInfo['bestseller_name'],$yourSellerNames)){
							$listing->is_bestseller = 'Y';
							$listing->offer_state = 'Active';
						}else
							$listing->is_bestseller = 'N';
					}else{
						$listing->is_bestseller = '-';
						$listing->bestseller_name = '';
					}
					if(!empty($prodcutInfo['bestseller_price']))
						$listing->bestseller_price = $prodcutInfo['bestseller_price'];
					//echo "\n final listing model is: \n";  //liangtest 
					//print_r($listing);//liang test
					
					if(!$listing->save()){
						$rtn['success'] = false;
						$rtn['message'] = "\n ".$prodcutInfo['product_id']." failure to save to db :".print_r($listing->getErrors());
						echo "\n ".$prodcutInfo['product_id']." failure to save to db :".print_r($listing->getErrors());
					}
				}
			}
			$current_time=explode(" ",microtime());
			$time4=round($current_time[0]*1000+$current_time[1]*1000);
			$run_time = $time4 - $time3; //这个得到的$time是以 ms 为单位的
			//echo "finished for step 3,use time $run_time ms \n";
			
			echo "\n save offer info to db successed, start to save terminator history \n";
			$rtn = self::updateTerminatorHistory($uid,$prodcutInfo,$is_base64);
			if($rtn['success']){
				echo "\n update terminator history successed \n";
			}else{
				echo "\n update terminator history failed, error: ".$rtn['message']."\n";
			}
			
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = is_array($e->getMessage())?json_encode($e->getMessage()):$e->getMessage();
			echo $e->getTraceAsString();
		}
		
		return $rtn;
	}
	
	public static function updateTerminatorHistory($uid,$prodcutInfo, $is_base64=true){
		$rtn['success']=true;
		$rtn['message']='';
		if (is_string($prodcutInfo) && $is_base64){
			//if string then base64 decode
			$prodcutInfo = base64_decode($prodcutInfo);
			//if string then json decode
			if (is_string($prodcutInfo)){
				$prodcutInfo = json_decode($prodcutInfo,true);
			}
		}
		try{
			//variant(变体)商品特殊处理:只记录父产品，子产品全部复制父产品信息
			if(!empty($prodcutInfo['product_id'])){
				$product_id = $prodcutInfo['product_id'];
				if(preg_match('/\-/', $prodcutInfo['product_id'])){//is a variant child
					$productIdStr = explode('-', $prodcutInfo['product_id']);
					if(!empty($productIdStr[0])){
						$parent_prod_id = $productIdStr[0];
						$product_id = $parent_prod_id;
						$terminator_record = new CdiscountOfferTerminator();
						$terminator_record->is_parent_product = 'Y';
					}
				}else{//simple offer
					$terminator_record = new CdiscountOfferTerminator();
					$terminator_record->is_parent_product = 'N';
				}
				
				$terminator_record->create = TimeUtil::getNow();
				$terminator_record->uid = $uid;
				$terminator_record->product_id = $product_id;
				
				if(!empty($prodcutInfo['bestseller_name'])){
					$terminator_record->bestseller_name = $prodcutInfo['bestseller_name'];
				}else{//如果bestseller_name缺失，表示商品无卖家，将商品设置为unActive
					/* 由于CD接口的不稳定，导致不时会有查询返回不了seller数据，导致这个判断反而成为累赘，因此屏蔽	@2017-08-07 liang
					if(!empty($parent_prod_id)){
						CdiscountOfferList::updateAll(['offer_state'=>'Inactive'],'parent_product_id = :parent_product_id',[':parent_product_id'=>$parent_prod_id]);
					}else{
						CdiscountOfferList::updateAll(['offer_state'=>'Inactive'],'product_id = :product_id',[':product_id'=>$product_id]);
					}
					*/	
				}
				//++++++++++++liang 2016-11-23
				//判断用户辖下是否有店铺获得bestseller,是否有变化
				$is_change=false;
				$change_type='';
				$lastTerminatorRecode = CdiscountOfferTerminator::find()->where(['product_id'=>$product_id,'uid'=>$uid])->orderBy('id DESC')->limit(1)->one();
				if(empty($lastTerminatorRecode)){
					$is_change=false;
				}else{ 
					$lastBestSeller = $lastTerminatorRecode->bestseller_name;
					if($lastBestSeller==$prodcutInfo['bestseller_name']){
						$is_change=false;
					}else{
						$yourSellerAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->all();
						$yourSellerNames = [];
						foreach ($yourSellerAccounts as $account){
							$yourSellerNames[]=$account->shopname;
						}
						//定制用户通过excel导入的话可能没有绑定的，需要通过redis补全
						$customizationUsers = CdiscountOfferTerminatorHelper::$customizationUsers;
						if(in_array($uid, $customizationUsers)){
							$customizationUserShops = RedisHelper::RedisGet("CDOT_CustomizationUserShops","user_$uid");
							$customizationUserShops = empty($customizationUserShops)?[]:json_decode($customizationUserShops,true);
							foreach ($customizationUserShops as $shop_name){
								if(!in_array($shop_name,$yourSellerNames))
									$yourSellerNames[] = $shop_name;
							}
						}
						
						//最后记录为自己是BS
						if(in_array($lastBestSeller,$yourSellerNames)){
							if(!empty($prodcutInfo['bestseller_name']) && !in_array($prodcutInfo['bestseller_name'],$yourSellerNames) ){
								$is_change=true;
								$change_type = 'push';//加入到被抢记录redis数组
							}
						}
						//最后记录为自己不是BS
						if(!in_array($lastBestSeller,$yourSellerNames)){
							if(in_array($prodcutInfo['bestseller_name'],$yourSellerNames) ){
								$is_change=true;
								$change_type = 'delete';//从被抢记录redis数组中移除
							}
						}
					}
				}//+++++++++++++
				
				if(!empty($prodcutInfo['bestseller_price']))
					$terminator_record->bestseller_price = $prodcutInfo['bestseller_price'];
				if(!$terminator_record->save()){
					$rtn['success']=false;
					$rtn['message'].="\n uid:$uid, product_id:".$product_id." failure to save to db :".print_r($terminator_record->getErrors());
				}
				//+++++++++++++卖家BS夺回和失去是，记录到redis
				if(!empty($is_change) && !empty($change_type)){
					$redis_oper = 'N/A';
					if($change_type=='push'){
						$redis_oper = CdiscountOfferTerminatorHelper::pushProductIdToLostBsRedis($product_id,$uid);
					}
					if($change_type=='delete'){
						$redis_oper = CdiscountOfferTerminatorHelper::deleteProductIdFromLostBsRedis($product_id,$uid);
					}
					echo "\n $product_id bestseller is changed, change type:$change_type, redis_oper:".print_r($redis_oper);
				}
				//++++++++++++
			}else {
				$rtn['success']=false;
				$rtn['message']='product_id lost';
				echo "\n product_id lost;";
				return $rtn;
			}
			
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = is_array($e->getMessage())?json_encode($e->getMessage()):$e->getMessage();
			echo $rtn['message'];
		}
		
		return $rtn;
	}
	
	/**
	 获取订cd单时更新相关产品的信息
	
	 */
	public static function syncProdInfoWhenGetOrderDetail($uid,$product_id=[],$priority=2,$seller_id=''){
	
		try{
			$field_list=array('product_id','img','title','description','brand');
			$site = '';
			$callback = 'eagle\modules\listing\helpers\CdiscountOfferSyncHelper::webSiteInfoToDb($puid,$prodcutInfo,1,$seller);';
			$rtn = HtmlCatcherHelper::requestCatchHtml($uid,$product_id,'cdiscount',$field_list,$site,$callback,$falg=true,$priority,['seller'=>$seller_id]);
			echo "\n syncProdInfoWhenGetOrderDetail---uid=$uid,product_id=".(is_array($product_id)?json_encode($product_id):$product_id).",rtn:".json_encode($rtn);//test
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		return $rtn;
	}
	
	/*
	 * 手动调用，立即同步无图片的offer
	 */
	public static function syncProdInfoByAdmin($uid,$product_id=[],$priority=1,$seller_id=''){
		try{
			$field_list=array('product_id','img','title','description','brand');
			$site = '';
			$callback = 'eagle\modules\listing\helpers\CdiscountOfferSyncHelper::webSiteInfoToDb($puid,$prodcutInfo,1,$seller);';
			$rtn = HtmlCatcherHelper::requestCatchHtml($uid,$product_id,'cdiscount',$field_list,$site,$callback,$falg=true,$priority,['seller'=>$seller_id],$needRefresh=true);
			echo "\n syncProdInfoByAdmin---uid=$uid,product_id=".(is_array($product_id)?json_encode($product_id):$product_id);//test
		}catch (\Exception $e) {
		$rtn['success'] = false;
		$rtn['message'] = $e->getMessage();
		}
		return $rtn;
	}
	
	public static function getSellerInfo($token){
		$timeout=240; //s
		echo "\n enter function : getSellerInfo";
	
		$config = array('tokenid' => $token);
		$get_param['config'] = json_encode($config);
	
		$retInfo=CdiscountProxyConnectHelper::call_Cdiscount_api("GetSellerInfo",$get_param,$post_params=array(),$timeout );
	
		return $retInfo;
	}
	/*
	public static function saveCdiscountProdToEagle(){
		
	}
	*/
	
	
	public static function getOfferListByCondition($params=[]){
		$model = new CdiscountOfferList();
		$attrs = $model->getAttributes();
		$query = CdiscountOfferList::find()->where(" product_id<>'' and product_id is not null ");
		if(!empty($params['per-page'])){
			$pageSize = (int)$params['per-page'];
			//unset($params['per-page']);
		}else 
			$pageSize = 100;
		if(!empty($params['sort'])){
			unset($params['sort']);
		}
		if(!empty($params['orderBy'])){
			$orderBy = $params['orderBy'];
			unset($params['orderBy']);
		}
		foreach ($params as $column=>$value){
			if(in_array($column, ['per-page','sort','orderBy','page']))
				continue;
			
			if($column=='keyword'){
				$keywords = trim($value);
				$arr_keywords = explode(';', $keywords);
				if( !empty($arr_keywords) && count($arr_keywords)>1 ){
					$keyword_arr=[];
					foreach ($arr_keywords as $keyword){
						$keyword = trim($keyword);
						if(!empty($keyword))
							$keyword_arr[] = $keyword;
					}
					if(!empty($keyword_arr))
						$query->andWhere( ['in','product_id',$keyword_arr]);
				}else{
					$query->andWhere([ 'or', ['like','product_ean',$keywords],
							['like','product_id',$keywords],
							['like','seller_product_id',$keywords],
							['like','name',$keywords],
							]);
				}
				
			}
			elseif($column=='offer_state'){
				//is null default to Active
				if($value=='Active')
					$query->andWhere("`offer_state`='Active' or `offer_state` is null");
				else
					$query->andWhere("`offer_state`='unActive' or `offer_state`='Inactive'");
			}
			elseif($column=='min_price'){
				$min_price = floatval($value);
				$query->andWhere(" price >= $min_price ");
			}
			elseif($column=='max_price'){
				$max_price = floatval($value);
				$query->andWhere(" price <= $max_price ");
			}
			elseif($column=='is_bestseller'){
				if($value=='Y')
					$query->andWhere(['is_bestseller'=>'Y']);
				elseif($value=='N')
					$query->andWhere(['is_bestseller'=>'N']);
				elseif($value=='-')
					$query->andWhere(['is_bestseller'=>['-','',null]]);
			}
			elseif($column=='focuse_status'){
				if($value=='F')
					$query->andWhere(['concerned_status'=>'F'])->andWhere("terminator_active is null or terminator_active='Y' ");
				elseif($value=='N')
					$query->andWhere(['concerned_status'=>'N']);
				elseif($value=='H')
					$query->andWhere(['concerned_status'=>'H'])->andWhere("terminator_active is null or terminator_active='Y' ");
				else 
					$query->andWhere(['concerned_status'=>'I']);
			}
			elseif($column=='lostbs' && !empty($value)){
				$puid = \Yii::$app->user->identity->getParentUid();
				//$lostbsOffers = \Yii::$app->redis->hget("CdOffernNewlyLostBestSeller","user_$puid");
				$lostbsOffers = RedisHelper::RedisGet("CdOffernNewlyLostBestSeller","user_$puid");
				if(!empty($lostbsOffers)){
					$lostbsOffers=json_decode($lostbsOffers,true);
				}
				
				if(!empty($lostbsOffers))
					$query->andWhere(['or',['product_id'=>$lostbsOffers],['parent_product_id'=>$lostbsOffers] ]);
				else{
					$offerList['pagination']=[];
					$offerList['rows']=[];
					return $offerList;
				}
			}
			elseif($column=='t_active'){
				if($value=='N')
					$query->andWhere(['terminator_active'=>'N']);
				else 
					$query->andWhere("`terminator_active`='Y' or `terminator_active` is null");
			}
			elseif($column=='seller_id' && !empty($value)){
				$query->andWhere(" `seller_id`='$value' or `shopname`='$value' ");
			}
			else{
				if(array_key_exists($column,$attrs))
					$query->andWhere([$column=> $value]);
			}
		}
		$offerList=[];
		
		//$time1=time();// liang test
		
		$pagination = new Pagination([
				//'pageSize' => $pageSize,
				'defaultPageSize' => 50,
				'totalCount' =>$query->count(),
				'pageSizeLimit'=>[20,500],//每页显示条数范围
				]);
		$offerList['pagination'] = $pagination;
		//var_dump($params);
		//$commandQuery = clone $query;
		//echo "<br>".$commandQuery->createCommand()->getRawSql()."<br>";
		//$time2=time();// liang test
		//$run_time = $time2 - $time1;
		//$journal_id = SysLogHelper::InvokeJrn_Create("Listing",__CLASS__, __FUNCTION__ , array('step1',$run_time));
		
		if(!empty($orderBy))
        $query->orderBy($orderBy);
		
		$query->limit($pagination->limit)
			->offset($pagination->offset);
		
		
// 		$commandQuery = clone $query;
// 		echo "<br>".$commandQuery->createCommand()->getRawSql()."<br>";
		        
		$offerRows = $query->asArray()
			->all();
		
		//$time3=time();// liang test
		//$run_time = $time3 - $time2;
		//$journal_id = SysLogHelper::InvokeJrn_Create("Listing",__CLASS__, __FUNCTION__ , array('step2',$run_time));
		
		$offer_product_ids = [];
		$offerData = [];
		foreach ($offerRows as $row){
			$offerData[$row['product_id']] = $row;
			$thisId = '';
			if(!empty($row['parent_product_id']))
				$thisId = $row['parent_product_id'];
			else 
				$thisId = $row['product_id'];
			if(!empty($thisId) && !in_array($thisId, $offer_product_ids))
				$offer_product_ids[] = $thisId;
		}
		
		//$time4=time();// liang test
		//$run_time = $time4 - $time3;
		//$journal_id = SysLogHelper::InvokeJrn_Create("Listing",__CLASS__, __FUNCTION__ , array('step3',$run_time));
		
		$last_histroy_data = [];
		if(!empty($offer_product_ids)){
			$query_history_ids = "select max(`id`) id  from `cdiscount_offer_terminator` where `product_id` in (".'\''. implode('\',\'', $offer_product_ids) .'\''.")  group by `product_id`  ORDER BY `id` DESC";
			$command = \Yii::$app->subdb->createCommand($query_history_ids);
			$last_history_ids = $command->queryAll();
			$history_ids = [];
			foreach ($last_history_ids as $h_row){
				$history_ids[] = $h_row['id'];
			}
			if(!empty($history_ids)){
				$terminator_histroy_sql = "select * from `cdiscount_offer_terminator` where `id` in (".implode(',', $history_ids).")";
				//echo $terminator_histroy_sql;
				$command = \Yii::$app->subdb->createCommand($terminator_histroy_sql);
				$last_terminator_histroy = $command->queryAll();
				foreach ($last_terminator_histroy as $histroy){
					$last_histroy_data[$histroy['product_id']] = $histroy;
				}
			}
		}
		
		//$time5=time();// liang test
		//$run_time = $time5 - $time4;
		//$journal_id = SysLogHelper::InvokeJrn_Create("Listing",__CLASS__, __FUNCTION__ , array('step4',$run_time));
		
		//var_dump($last_histroy_data);
		foreach ($offerData as $product_id=>&$product){
			if(preg_match('/\-/', $product_id)){//is a variant child
				$productIdStr = explode('-', $product_id);
				if(!empty($productIdStr[0])){
					$parent_prod_id = $productIdStr[0];
					if(isset($last_histroy_data[$parent_prod_id]) && $last_histroy_data[$parent_prod_id]['is_parent_product']=='Y'){
						$product['last_terminator_time'] = empty($last_histroy_data[$parent_prod_id]['create'])?'':$last_histroy_data[$parent_prod_id]['create'];
					}
				}
			}else{
				if(isset($last_histroy_data[$product_id]) && $last_histroy_data[$product_id]['is_parent_product']=='N'){
					$product['last_terminator_time'] = empty($last_histroy_data[$product_id]['create'])?'':$last_histroy_data[$product_id]['create'];
				}
			}
		}
		
		//$time6=time();// liang test
		//$run_time = $time5 - $time5;
		//$journal_id = SysLogHelper::InvokeJrn_Create("Listing",__CLASS__, __FUNCTION__ , array('step5',$run_time));
		
		$offerList['rows']=$offerData;
		
		return $offerList;
	}
	
    //每个cd账号设置关注的上限
	public static function getUserAddiFellow($uid){
		//$addi_fellow = \Yii::$app->redis->hget("CdiscountAccountAddiFellow","user_$uid");
		$addi_fellow = RedisHelper::RedisGet("CdiscountAccountAddiFellow","user_$uid");
		//如果redis有记录，直接返回redis记录；
		if(!empty($addi_fellow))
			return $addi_fellow;
		//无redis记录，搜表
		$CdAccountVipInfo = CdiscountAccountsApiHelper::getCdAccountVipInfo($uid);
		if(!empty($CdAccountVipInfo[$uid]['addi_follow']))
			$addi_fellow = (int)$CdAccountVipInfo[$uid]['addi_follow'];
		else 
			$addi_fellow = 0;
		
		//搜索到记录，或默认后，记录到redis
		//\Yii::$app->redis->hset("CdiscountAccountMaxFellow","user_$uid",$addi_fellow);
		RedisHelper::RedisSet("CdiscountAccountMaxFellow","user_$uid",$addi_fellow);
		return $addi_fellow;
	}
	//每个cd账号设置热销的上限
	public static function getUserAddiHotSale($uid){
		//$addi_hot_sale = \Yii::$app->redis->hget("CdiscountAccountAddiHotSale","user_$uid");
		$addi_hot_sale = RedisHelper::RedisGet("CdiscountAccountAddiHotSale","user_$uid");
		//如果redis有记录，直接返回redis记录；
		if(!empty($addi_hot_sale))
			return $addi_hot_sale;
		//无redis记录，搜表
		$CdAccountVipInfo = CdiscountAccountsApiHelper::getCdAccountVipInfo($uid);
		if(!empty($CdAccountVipInfo[$uid]['addi_hot_sale'])){
			$addi_hot_sale = (int)$CdAccountVipInfo[$uid]['addi_hot_sale'];
		}else {
			$addi_hot_sale = 0;
		}
		
		//搜索到记录，或默认后，记录到redis
		//\Yii::$app->redis->hset("CdiscountAccountAddiHotSale","user_$uid",$addi_hot_sale);
		RedisHelper::RedisSet("CdiscountAccountAddiHotSale","user_$uid",$addi_hot_sale);
		return $addi_hot_sale;
	}
	
	/*
	 * 获取用户的erp套餐额外额度
	 * @parmas	$uid
	 * @parmas	$type	H or F
	 * 2018-08-17	liang
	 */
	public static function getCdTerminatorErpVipAddiQuota($uid,$type){
		$erp_addi_quota = 0;
		return $erp_addi_quota;
	}
	
	/*
	 * 设置offer未关注状态
	* each $offer[] like : offer_id+'@@'+seller_id
	*/
	public static function setHotSaleOffer($offers=[]){
		$rtn=['success'=>true,'message'=>''];
		$offerGroupBySeller = [];
		$offer_ids = [];
		foreach ($offers as $id_puls_seller){
			$tmp_cup=[];
			$o_id='';
			$s_id='';
			$tmp_cup = explode('@@',$id_puls_seller);
			if(!empty($tmp_cup[0])){
				$o_id = $tmp_cup[0];
				$offer_ids[] = $o_id;
			}
			if(!empty($tmp_cup[1]))
				$s_id = $tmp_cup[1];
			if(!empty($o_id) && !empty($s_id))
				$offerGroupBySeller[$s_id][] = $o_id;
		}
		//获取用户账号最大关注数
		$uid = \Yii::$app->user->id;
		$maxHotSale = [];
		$used_quota_tmp = 0;//已用的vip额度,计算临时用
		$default_hot_sale = CdiscountAccountsApiHelper::$CdiscountTerminatorDefaultMaxHotSale;//默认所有用户每个CD账号的爆款上限
		$user_addi_hot_sale = self::getUserAddiHotSale($uid);
		//var_dump($user_addi_hot_sale);
		//事先对所有账号操作是否超限做判定 start
		$seller_concerned_count = CdiscountOfferList::find()->select("count(*) count,`seller_id`")->where(['concerned_status'=>'H'])->andWhere("`terminator_active` is null or `terminator_active`='Y' ")->groupBy("seller_id")->asArray()->all();
		
		if(!empty($seller_concerned_count)){
			foreach ($seller_concerned_count as $row_count){
				$total_count = 0;//设置后的该账号总爆款数
				$total_count += (int)$row_count['count'];
				if(!empty($offerGroupBySeller[$row_count['seller_id']])){
					$total_count += count($offerGroupBySeller[$row_count['seller_id']]);
					unset($offerGroupBySeller[$row_count['seller_id']]);//计算完后unset对应账号数组
				}
				$used_quota_tmp += ($default_hot_sale-$total_count < 0)?$total_count-$default_hot_sale:0;
				
				if($used_quota_tmp > $user_addi_hot_sale){
					$rtn['success'] = false;
					$rtn['message'] .= '监视的爆款商品数量将超过限额，操作终止。';
				}
			}
		}
		//unset后的账号数组依然有数据，或者$seller_concerned_count为空，表示有账号是offer表里没有的设置过爆款的
		if(!empty($offerGroupBySeller)){
			foreach ($offerGroupBySeller as $seller_id=>$offer_arr){
				$total_count = count($offer_arr);
				$used_quota_tmp += ($default_hot_sale-$total_count < 0)?$total_count-$default_hot_sale:0;
				
				if($used_quota_tmp > $user_addi_hot_sale){
					$rtn['success'] = false;
					$rtn['message'] .= '监视的爆款商品数量将超过限额，操作终止。';
				}
			}
		}
		if(!$rtn['success']){
			return $rtn;
		}
		//事先对所有账号操作是否超限做判定 end
		try{
			CdiscountOfferList::updateAll(['concerned_status'=>'H','terminator_active'=>null],"id in (".implode(',', $offer_ids).")");
			foreach ($offer_ids as $offer_id){
				$offer_data = CdiscountOfferList::findOne($offer_id);
				if(!empty($offer_data->product_id)){
					CdiscountOfferTerminatorHelper::unsetFollowedProduct($uid, $offer_data->product_id);
					CdiscountOfferTerminatorHelper::setHotSaleProduct($uid, $offer_data->product_id);
				}
			}
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		
		//读取update后的爆款数
		$rtn['remaining'] = '';
		$seller_concerned_count = CdiscountOfferList::find()->select("count(*) count,`seller_id`")->where(['concerned_status'=>'H'])->andWhere("`terminator_active` is null or `terminator_active`='Y' ")->groupBy("seller_id")->asArray()->all();
		
		$used_quota = 0;
		$used_msg = '';
		foreach ($seller_concerned_count as $row_count){
			$total_count = $row_count['count'];
			
			$used_msg .= '账号'.$row_count['seller_id'].'已设置'.$total_count.'个爆款监视';
			if($total_count>$default_hot_sale){
				$used_msg.='，使用了 <span style="color:red">'.($total_count-$default_hot_sale).'</span> 个vip爆款监视额度；<br>';
				$used_quota += $total_count-$default_hot_sale;
			}else{
				$used_msg.='，还有 <span style="color:green">'.($default_hot_sale-$total_count).'</span> 个默认爆款监视额度可用；<br>';
			}
		}
		$rtn['remaining'] .= '<br>账号还有 <span style="color:green">'.($user_addi_hot_sale-$used_quota).'</span> 个vip爆款监视额度可用。<br>'.$used_msg;
		
		return $rtn;
	}
	
	/*
	 * 设置offer为关注状态
	 * each $offer[] like : offer_id+'@@'+seller_id
	 */
	public static function setConcernedOffer($offers=[]){
		$rtn=['success'=>true,'message'=>''];
		$offerGroupBySeller = [];
		$offer_ids = [];
		foreach ($offers as $id_puls_seller){
			$tmp_cup=[];
			$o_id='';
			$s_id='';
			$tmp_cup = explode('@@',$id_puls_seller);
			if(!empty($tmp_cup[0])){
				$o_id = $tmp_cup[0];
				$offer_ids[] = $o_id;
			}
			if(!empty($tmp_cup[1]))
				$s_id = $tmp_cup[1];
			if(!empty($o_id) && !empty($s_id))
				$offerGroupBySeller[$s_id][] = $o_id;
		}
		//获取用户账号最大关注数
		$uid = \Yii::$app->user->id;
		$maxConcerned = [];
		$used_quota_tmp = 0;//已用的vip额度,计算临时用
		$default_fellow = CdiscountAccountsApiHelper::$CdiscountTerminatorDefaultMaxFellow;//默认所有用户每个CD账号的关注上限
		$user_addi_fellow = self::getUserAddiFellow($uid);
		//事先对所有账号操作是否超限做判定 start
		$seller_concerned_count = CdiscountOfferList::find()->select("count(*) count,`seller_id`")->where(['concerned_status'=>'F'])->andWhere("`terminator_active` is null or `terminator_active`='Y' ")->groupBy("seller_id")->asArray()->all();
		//print_r($seller_concerned_count);//
		if(!empty($seller_concerned_count)){
			foreach ($seller_concerned_count as $row_count){
				$total_count = 0;//设置后的该账号总爆款数
				$total_count += (int)$row_count['count'];
				if(!empty($offerGroupBySeller[$row_count['seller_id']])){
					$total_count += count($offerGroupBySeller[$row_count['seller_id']]);
					unset($offerGroupBySeller[$row_count['seller_id']]);//计算完后unset对应账号数组
				}
				$used_quota_tmp += ($default_fellow-$total_count < 0)?$total_count-$default_fellow:0;
				
				if($used_quota_tmp > $user_addi_fellow){
					$rtn['success'] = false;
					$rtn['message'] .= '关注的商品数量将超过限额，操作终止。';
				}
			}
		}
		//unset后的账号数组依然有数据，或者$seller_concerned_count为空，表示有账号是offer表里没有的设置过关注的
		if(!empty($offerGroupBySeller)){
			foreach ($offerGroupBySeller as $seller_id=>$offer_arr){
				$total_count = count($offer_arr);
				$used_quota_tmp += ($default_fellow-$total_count < 0)?$total_count-$default_fellow:0;
				
				if($used_quota_tmp > $user_addi_fellow){
					$rtn['success'] = false;
					$rtn['message'] .= '关注的商品数量将超过限额，操作终止。';
				}
			}
		}
		if(!$rtn['success']){
			return $rtn;
		}
		//事先对所有账号操作是否超限做判定 end
		try{
			CdiscountOfferList::updateAll(['concerned_status'=>'F','terminator_active'=>null],"id in (".implode(',', $offer_ids).")");
			foreach ($offer_ids as $offer_id){
				$offer_data = CdiscountOfferList::findOne($offer_id);
				if(!empty($offer_data->product_id)){
					CdiscountOfferTerminatorHelper::setFollowedProduct($uid, $offer_data->product_id);
					CdiscountOfferTerminatorHelper::unsetHotsaleProduct($uid, $offer_data->product_id);
				}
			}
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		
		//读取update后的关注数
		$rtn['remaining'] = '';
		$seller_concerned_count = CdiscountOfferList::find()->select("count(*) count,`seller_id`")->where(['concerned_status'=>'F'])->andWhere("`terminator_active` is null or `terminator_active`='Y' ")->groupBy("seller_id")->asArray()->all();
		$used_quota = 0;
		$used_msg = '';
		foreach ($seller_concerned_count as $row_count){
			$total_count = $row_count['count'];
				
			$used_msg .= '账号'.$row_count['seller_id'].'已设置'.$total_count.'个关注监视';
			if($total_count>$default_fellow){
				$used_msg.='，使用了 <span style="color:red">'.($total_count-$default_fellow).'</span> 个vip关注额度；<br>';
				$used_quota += $total_count-$default_fellow;
			}else{
				$used_msg.='，还有 <span style="color:green">'.($default_fellow-$total_count).'</span> 个默认关注额度可用；<br>';
			}
		}
		$rtn['remaining'] .= '<br>账号还有 <span style="color:green">'.($user_addi_fellow-$used_quota).'</span> 个vip关注额度可用。<br>'.$used_msg;
		
		return $rtn;
	}
	
	/**
	 * 设置offer为忽略
	 */
	public static function setConcernedIgnoreOffer($offers=[]){
		$rtn=['success'=>true,'message'=>''];
		$puid = \Yii::$app->user->identity->getParentUid();
		try{
			CdiscountOfferList::updateAll(['concerned_status'=>'I','terminator_active'=>null],"id in (".implode(',', $offers).")");
			foreach ($offers as $offer_id){
				$offer_data = CdiscountOfferList::findOne($offer_id);
				if(!empty($offer_data->product_id)){
					CdiscountOfferTerminatorHelper::unsetFollowedProduct($puid, $offer_data->product_id);
					CdiscountOfferTerminatorHelper::unsetHotsaleProduct($puid, $offer_data->product_id);
				}
			}
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		//OperationLogHelper::log('CdiscountListing', , $operation)
		return $rtn;
	}
	
	/**
	 * 设置offer为普通
	 */
	public static function setConcernedNormalOffer($offers=[]){
		$rtn=['success'=>true,'message'=>''];
		$puid = \Yii::$app->user->identity->getParentUid();
		try{
			CdiscountOfferList::updateAll(['concerned_status'=>'N','terminator_active'=>null],"id in (".implode(',', $offers).")");
			foreach ($offers as $offer_id){
				$offer_data = CdiscountOfferList::findOne($offer_id);
				if(!empty($offer_data->product_id)){
					CdiscountOfferTerminatorHelper::unsetFollowedProduct($puid, $offer_data->product_id);
					CdiscountOfferTerminatorHelper::unsetHotsaleProduct($puid, $offer_data->product_id);
				}
			}
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		return $rtn;
	}
	
	/*
	 * 重新恢复由于vip等级下降而导致的失效关注/爆款设置
	 */
	public static function reActiveTerminatorStatus($offers=[]){
		$rtn=['success'=>true,'message'=>'','remaining'=>''];
		$offerGroupBySeller = [];
		$offer_ids = [];
		foreach ($offers as $id_puls_seller){
			$tmp_cup=[];
			$tmp_cup = explode('@@',$id_puls_seller);
			$offer_level='';
			$o_id='';
			$s_id='';
			if(count($tmp_cup)<3){
				//offer 的 id / seller / 关注状态 ,一项或多项缺失
				continue;
			}
			if(!empty($tmp_cup[2]))
				$offer_level = strtoupper($tmp_cup[2]);
			else
				continue;
			
			if(!empty($tmp_cup[0]))
				$o_id = $tmp_cup[0];
			else
				continue;
			
			if(!empty($tmp_cup[1]))
				$s_id = $tmp_cup[1];
			else 
				continue;
			
			if(!empty($o_id) && !empty($s_id) && !empty($offer_level))
				$offer_ids[$offer_level][] = $o_id.'@@'.$s_id;
		}
		
		if(!empty($offer_ids)){
			foreach ($offer_ids as $level=>$ids){
				if($level=='H'){
					$result = self::setHotSaleOffer($ids);
					if(!$result['success']){
						$rtn['success'] = false;
						$rtn['message'].= '<span style="color:red">爆款监视恢复失败：'.$result['message'].'</span>';
					}
				}
				elseif($level=='F'){
					$result = self::setConcernedOffer($ids);
					if(!$result['success']){
						$rtn['success'] = false;
						$rtn['message'].= '<span style="color:red">关注恢复失败：'.$result['message'].'</span>';
					}
				}
				$rtn['remaining'] .= empty($result['remaining'])?'':$result['remaining'];
			}
		}
		return $rtn;
	}
	
	
	public static function getOfferInfo($params=[],$seller=''){
		if(empty($params))
			return array();
		
		$model = new CdiscountOfferList();
		$attrs = $model->getAttributes();
		
		$query = CdiscountOfferList::find()->where("seller_id<>'' and seller_id is not null");
		if(!empty($seller))
			$query->andWhere(['seller_id'=>$seller]);
		foreach ($params as $key=>$value){
			$key = strtolower($key);
			if(in_array($key,$attrs)){
				$query->andWhere([$key=>$value]);
			}
		}
		$offer = $query->asArray()->one();
		return $offer;
		
	}
	
	/*
	 * 删除多余、过于频繁的terminator记录
	 */
	public static function DataFix_TerminatorHistory($uid){
		$hotsales = CdiscountOfferList::find()->where(['concerned_status'=>'H'])->asArray()->all();
		foreach ($hotsales as $h){
			$now = TimeUtil::getNow();
			$nextTime = date('Y-m-d H:i:s',strtotime('-3 hours'));
			do{
				$lastHistroy_in_time = CdiscountOfferTerminator::find()->select("id")->where(['product_id'=>$h['product_id']])
				->andWhere("`create`>='$nextTime' and `create`<'$now' ")
				->orderBy("`id` DESC")
				->asArray()->one();
				$last_id = empty($lastHistroy_in_time['id'])?0:(int)$lastHistroy_in_time['id'];
				if(!empty($last_id)){
					$sql_del = "delete from `cdiscount_offer_terminator`
					where `create`>='$nextTime' and `create`<'$now' and `id`<>$last_id and `product_id`='".$h['product_id']."' ";
					$command = \Yii::$app->subdb->createCommand($sql_del);
					echo "\n ".$command->getRawSql();
					$command->execute();
				}
				$now = $nextTime;
				$tmp_time = strtotime($nextTime)-3600*3;
				$nextTime = date('Y-m-d H:i:s',$tmp_time);
			}while ( strtotime($nextTime) > strtotime('2016-05-28 19:52:53'));
		}
	}
	
	/*
	 * 删除用户所有非关注、非爆款的offer
	 */
	public static function  DataFix_OfferList($uid,$sellers){
		$sellers_str = implode('\',\'', $sellers);
		CdiscountOfferList::deleteAll(" (concerned_status not in ('H','F') and seller_id in ('$sellers_str')) or seller_id not in ('$sellers_str') ");
		SaasCdiscountUser::updateAll(['fetcht_offer_list_time'=>null],['uid'=>$uid]);
	}
	
	/*
	 * 获取用户CD跟卖终结者的相关额度信息
	 * 已用额度、未用额度
	 * VIP额度、剩余VIP额度
	 * 账号信息
	 */
	public static function getUserQuotaInfo($uid){
		$rtn=['accounts'=>[],'vip_info'=>[]];
		//所有绑定的cd账号信息
		$accounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->asArray()->all();
		//用户当前vip信息
		$vipInfo = CdiscountAccountsApiHelper::getCdAccountVipInfo($uid);
		if(!empty($vipInfo[$uid]))
			$vipInfo = $vipInfo[$uid];
		else 
			$vipInfo=[];
		
// 		var_dump($vipInfo);exit();
		//加上erp-vip额度	2018-08-17	liang
		$vipInfo['erp_addi_follow'] = self::getCdTerminatorErpVipAddiQuota($uid, 'F');
		$vipInfo['erp_addi_hot_sale'] = self::getCdTerminatorErpVipAddiQuota($uid, 'H');
		
		
		if(!empty($vipInfo['addi_info']))
			$addi_info = json_decode($vipInfo['addi_info'],true);
		if(empty($addi_info))
			$addi_info = [];
		//没有保存额度使用记录，需要重新计算
		if(empty($addi_info['used_quota_info'])){
			//echo "<br>used new data;<br>";
			$used_quota_info = [];//已使用的额度信息
			$seted_fellow=[];//已设置的关注统计，by seller_id
			$seller_fellow_count = CdiscountOfferList::find()->select("count(*) count,`seller_id`")->where(['concerned_status'=>'F'])->andWhere(['terminator_active'=>null])->groupBy("seller_id")->asArray()->all();
			foreach ($seller_fellow_count as $fellow_count){
				$seted_fellow[$fellow_count['seller_id']] = $fellow_count['count'];
			}
			
			$seted_hotsale=[];//已设置的爆款统计，by seller_id
			$seller_hotsale_count = CdiscountOfferList::find()->select("count(*) count,`seller_id`")->where(['concerned_status'=>'H'])->andWhere(['terminator_active'=>null])->groupBy("seller_id")->asArray()->all();
			foreach ($seller_hotsale_count as $hotsale_count){
				$seted_hotsale[$hotsale_count['seller_id']] = $hotsale_count['count'];
			}
			$used_hotsale_quota_tmp = 0;//已用的vip额度,计算临时用
			$used_fellow_quota_tmp = 0;//已用的vip额度,计算临时用
			$default_fellow = CdiscountAccountsApiHelper::$CdiscountTerminatorDefaultMaxFellow;//默认所有用户每个CD账号的关注上限
			$default_hotsale = CdiscountAccountsApiHelper::$CdiscountTerminatorDefaultMaxHotSale;//默认所有用户每个CD账号的关注上限
			$user_addi_fellow = self::getUserAddiFellow($uid);//用户vip额度
			$user_addi_hotsale = self::getUserAddiHotSale($uid);//用户vip额度
			
			foreach ($accounts as &$account){
				$account['hotsale'] = !empty($seted_hotsale[$account['username']])?$seted_hotsale[$account['username']]:0;//账号已设置爆款数
				$account['used_hotsale_quota'] = ($account['hotsale'] > $default_hotsale)?($account['hotsale']-$default_hotsale):0;//账号已用VIP爆款额度
				$used_hotsale_quota_tmp += $account['used_hotsale_quota'];
				$used_quota_info['used_hotsale_quota'][$account['username']] = $account['used_hotsale_quota'];//vip表记录账号使用额度
				$used_quota_info['seted_hotsale'][$account['username']] = $account['hotsale'];//vip表记录账号设置爆款数
				
				$account['fellow'] = !empty($seted_fellow[$account['username']])?$seted_fellow[$account['username']]:0;//账号已设置关注数
				$account['used_fellow_quota'] = ($account['fellow'] > $default_fellow)?($account['fellow']-$default_fellow):0;//账号已用VIP关注额度
				$used_fellow_quota_tmp += $account['used_fellow_quota'];
				$used_quota_info['used_fellow_quota'][$account['username']] = $account['used_fellow_quota'];//vip表记录账号使用额度
				$used_quota_info['seted_fellow'][$account['username']] = $account['fellow'];//vip表记录账号设置关注数
			}
			
			
			$vipInfo['remaining_hotsale_quota'] =$user_addi_hotsale - $used_hotsale_quota_tmp;//用户剩余爆款额度
			$vipInfo['remaining_fellow_quota'] = $user_addi_fellow - $used_fellow_quota_tmp;//用户剩余关注额度
			$used_quota_info['remaining_hotsale_quota'] = $vipInfo['remaining_hotsale_quota'];//vip表记录用户剩余爆款额度
			$used_quota_info['remaining_fellow_quota'] = $vipInfo['remaining_fellow_quota'];//vip表记录用户剩余关注额度
			$addi_info['used_quota_info'] = $used_quota_info;
			
			try {
				$sql_update = "update `saas_cdiscount_vip_user` set `addi_info`=:addi_info where `puid`=:puid ";
				$command = \Yii::$app->db->createCommand($sql_update);
				$command->bindValue (':puid', $uid, \PDO::PARAM_INT );
				$command->bindValue (':addi_info', json_encode($addi_info), \PDO::PARAM_STR );
				$command->execute();
			}catch (\Exception $e){
				echo "update vip addi_info to db Exception:".$e->getMessage();
			}
			
			$rtn=['accounts'=>$accounts,'vip_info'=>$vipInfo];
			return $rtn;
		}else{
			//echo "<br>used db data;<br>";
			$used_quota_info = $addi_info['used_quota_info'];
			$vipInfo['remaining_hotsale_quota'] = $used_quota_info['remaining_hotsale_quota'];
			$vipInfo['remaining_fellow_quota'] = $used_quota_info['remaining_fellow_quota'];
			
			$accounts = [];
			if(!empty($addi_info['used_quota_info']['seted_hotsale'])){
				foreach ($addi_info['used_quota_info']['seted_hotsale'] as $seller_id=>$seted){
					$accounts[$seller_id]['hotsale'] = $seted;
					$accounts[$seller_id]['username'] = $seller_id;
				}
			}
			if(!empty($addi_info['used_quota_info']['seted_fellow'])){
				foreach ($addi_info['used_quota_info']['seted_fellow'] as $seller_id=>$seted){
					$accounts[$seller_id]['fellow'] = $seted;
					$accounts[$seller_id]['username'] = $seller_id;
				}
			}
			if(!empty($addi_info['used_quota_info']['used_hotsale_quota'])){
				foreach ($addi_info['used_quota_info']['used_hotsale_quota'] as $seller_id=>$used){
					$accounts[$seller_id]['used_hotsale_quota'] = $used;
					$accounts[$seller_id]['username'] = $seller_id;
				}
			}
			if(!empty($addi_info['used_quota_info']['used_fellow_quota'])){
				foreach ($addi_info['used_quota_info']['used_fellow_quota'] as $seller_id=>$used){
					$accounts[$seller_id]['used_fellow_quota'] = $used;
					$accounts[$seller_id]['username'] = $seller_id;
				}
			}
			
			$rtn=['accounts'=>$accounts,'vip_info'=>$vipInfo];
			return $rtn;
		}
	}
	
	
	public static function purgeRedundantCdiscountOfferList($puid){
		$db_change = self::changeDBPuid($puid);
		if(!$db_change)
			return;
		
		$command = \Yii::$app->db->createCommand("select * from saas_cdiscount_user where uid=$puid");
		$rows = $command->queryAll();
		//remove the ones not having store name binded
		$sellerids = "''";
		$i = 1000;
		foreach ($rows as $row){
			$sellerids .= ",:sellerid_".$i;
			$i++ ;
		}
		
		$i = 1000;
		$command = Yii::$app->subdb->createCommand("delete from cdiscount_offer_list where seller_id not in ($sellerids)"  );
		foreach ($rows as $row){
			$command->bindValue(":sellerid_".$i, $row['username'], \PDO::PARAM_STR);
			$i++ ;
		}

		$affectRows = $command->execute();
		
		
		//remove the redudant
		$terminator_histroy_sql = "select distinct product_id,seller_id from `cdiscount_offer_list`  ";
	   
		//echo $terminator_histroy_sql;
		$command = \Yii::$app->subdb->createCommand($terminator_histroy_sql);
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$seller_id=$row['seller_id'];
			$product_id=$row['product_id'];
			$command = \Yii::$app->subdb->createCommand("select max(id) from cdiscount_offer_list where product_id='$product_id' and seller_id='$seller_id'");
			$maxId = $command->queryScalar();
			
			$command = \Yii::$app->subdb->createCommand("delete from cdiscount_offer_list where id<>$maxId and product_id='$product_id' and seller_id='$seller_id'");
			$maxId = $command->execute();
		}
	}
	
	/*
	 * data fix : 删除已经解绑的CD账号对应的listing，并且删除cdot_followed_product，cdot_hotsale_product的对应商品记录
	 * @author lzhl
	 */
	public static function purgeUnbindedCdiscountOfferList($puid){
		echo "\n Puid = $puid";
		$db_change = self::changeDBPuid($puid);
		if(!$db_change)
			return;
		$command = \Yii::$app->db->createCommand("select * from saas_cdiscount_user where uid=$puid");
		$rows = $command->queryAll();
		$bindingStore = [];
		foreach ($rows as $row){
			$bindingStore[] = $row['username'];
		}
		
		$unbindedListing = CdiscountOfferList::find()->select("product_id")->where(["not", ["seller_id"=>$bindingStore] ])->asArray()->all();
		$unbindedProductId=[];
		foreach ($unbindedListing as $unbinded){
			$unbindedProductId[] = $unbinded['product_id'];
		}
		$tmp_unbindedProductId = [];
		if(count($unbindedProductId)>100){
			$tmp_unbindedProductId = array_chunk($unbindedProductId,100);
		}else 
			$tmp_unbindedProductId[] = $unbindedProductId;
		
		foreach ($tmp_unbindedProductId as $tmp_ids){
			HotsaleProduct::deleteAll(['puid'=>$puid,'product_id'=>$tmp_ids]);
			FollowedProduct::deleteAll(['puid'=>$puid,'product_id'=>$tmp_ids]);
		}
		
		//remove the ones not having store name binded
		$sellerids = "''";
		$i = 1000;
		foreach ($rows as $row){
			$sellerids .= ",:sellerid_".$i;
			$i++ ;
		}
		$i = 1000;
		$command = Yii::$app->subdb->createCommand("delete from cdiscount_offer_list where seller_id not in ($sellerids)"  );
		foreach ($rows as $row){
			$command->bindValue(":sellerid_".$i, $row['username'], \PDO::PARAM_STR);
			$i++ ;
		}
		
		$affectRows = $command->execute();
	}
	
	/*
	 * 当删除CD绑定的时候，清除对应账号的listing数据，并释放使用掉的跟卖终结者quota
	 * @author lzhl
	 */
	public static function deleteOfferListAfterStoreUnbinded($seller_id,$puid=0){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		
		//删除cdot_followed_product，cdot_hotsale_product的seller商品记录
		$hotsales = CdiscountOfferList::find()->select(" product_id ")->where(['seller_id'=>$seller_id,'concerned_status'=>'H'])->asArray()->all();
		$unbindHotsales = [];
		foreach ($hotsales as $h){
			$unbindHotsales[] = $h['product_id'];
		}
		$fellowsales = CdiscountOfferList::find()->select(" product_id ")->where(['seller_id'=>$seller_id,'concerned_status'=>'F'])->asArray()->all();
		$unbindFellowsales = [];
		foreach ($fellowsales as $f){
			$unbindFellowsales[] = $h['product_id'];
		}
		HotsaleProduct::deleteAll(['puid'=>$puid,'product_id'=>$unbindHotsales]);
		FollowedProduct::deleteAll(['puid'=>$puid,'product_id'=>$unbindFellowsales]);
		
		//删listing
		CdiscountOfferList::deleteAll(['seller_id'=>$seller_id]);
		
		//删vip表addi_info
		$sql = "select * from `saas_cdiscount_vip_user` where `puid`=:puid";
		$command = \Yii::$app->db->createCommand($sql);
		$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
		$record = $command->queryOne();
		if(!empty($record)){
			$addi_info = empty($record['addi_info'])?[]:json_decode($record['addi_info'],true);
			if(isset($addi_info['used_quota_info']))
				unset($addi_info['used_quota_info']);
			
			$sql_update = "update `saas_cdiscount_vip_user` set `addi_info`=:addi_info where `puid`=:puid ";
			$command = \Yii::$app->db->createCommand($sql_update);
			$command->bindValue (':puid', $uid, \PDO::PARAM_INT );
			$command->bindValue (':addi_info', json_encode($addi_info), \PDO::PARAM_STR );
			$command->execute();
		}
		
		//删redis
		RedisHelper::RedisDel("CdiscountAccountMaxFellow","user_$uid" );
		RedisHelper::RedisDel("CdiscountAccountAddiFellow","user_$uid" );
		RedisHelper::RedisDel("CdiscountAccountMaxHotSale","user_$uid" );
		RedisHelper::RedisDel("CdiscountAccountAddiHotSale","user_$uid" );
	}
}