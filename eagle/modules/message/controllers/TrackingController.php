<?php

namespace eagle\modules\message\controllers;

use Yii;
use yii\web\Controller;
use eagle\modules\message\helpers\TrackingMsgHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\message\apihelpers\MessageApiHelper;

use yii\web\NotFoundHttpException;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\message\helpers\MessageBGJHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\GoogleHelper;
use yii\base\Action;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\models\SaasAliexpressUser;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\models\SysCountry;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\tracking\helpers\TrackingRecommendProductHelper;
use eagle\modules\order\helpers\OrderTrackingMessageHelper;

/**
 * 物流信息及推荐商品界面controller
 * 与eagle\modules\tracking\controllers;
 */


class TrackingController extends Controller{
	//public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man

	//页面ajax请求，查询RecommendProduct是否已经准备好
	public function actionCheckIsReady(){
		$rtn['isReady'] = true;
		$rtn['message'] = '';
		$puid = empty($_GET['puid'])?'':$_GET['puid'];
		$platform = empty($_GET['platform'])?'':$_GET['platform'];
		$site_id = empty($_GET['site_id'])?'':$_GET['site_id'];
		$seller_id = empty($_GET['seller_id'])?'':$_GET['seller_id'];
		$recom_prod_group = empty($_GET['recom_group'])?'':$_GET['recom_group'];
		$recom_prod_count = empty($_GET['recom_count'])?'':$_GET['recom_count'];
		
		if($platform=='' or $seller_id==''){
			$rtn['isReady'] = false;
			$rtn['message'] = 'platform or seller_id was not specified !';
		}else{
			$CustomizedRecommended = OrderTrackingMessageHelper::getTargetCustomizedRecommendedProds($puid,$platform, $seller_id,$recom_prod_group,$recom_prod_count);
			$rtn['isReady'] = true;
			
		}
		//$rtn['isReady'] = true;
		exit( json_encode($rtn) );
	}
	
	
	/**物流信息及推荐商品index
	 * isReady：用户推荐商品信息是否已经准备好；
	 * 若果isReady=true,则展示正常的有内容页面；
	 * 若果isReady=false,则展示loading页面，并等待后台生产推荐商品，ajax获取actionCheckIsReady，直到返回true后，展示正常的有内容页面
	 * 
	 * @return mixed
	 */
    public function actionIndex()
    {
		$isCheck =(isset($_GET['ischeck']))?$_GET['ischeck']:0;

    	$parmaStr = (isset($_GET['parcel']))?$_GET['parcel']:'';
    	$parmaStr = MessageHelper::decryptBuyerLinkParam($parmaStr);
    	$trackingInfo = new Tracking();
    	$orderInfo=[];
    	$orderItems=[];
    	$recommendProduct=[];
    	$errorMsg = '';
    	$layout=1;//默认layout设为1
    	$recom_prod_count = 8;//默认展示8个推荐商品
		$recom_prod_group = 0;//默认展示推荐商品分组
    	$consignee_country_code='EN';//默认展示英文内容
    	$order_platform = '';//订单来源平台
    	$seller_id='';
    	$site_id='';
    	$isReady = true;
    	//检查传入的用户参数
    	if(empty($parmaStr)){
    		$errorMsg='参数丢失!';
    	}
    	else{
    		$parmas = explode('-', $parmaStr);
    		if(count($parmas)<3){
    			$errorMsg='参数丢失!';
    		}else{
		    	//传入参数ok
		    	
		    	if(count($parmas)==2 || (count($parmas)==3 && $parmas[2]==0)){
	    			$puid = $parmas[0];
	    			$track_id = $parmas[1];
	    			//获取订单物流信息
	    			$info = TrackingMsgHelper::getTrackingInfo($puid, $track_id);
	    			//print_r($info);
	    			if($info['errorMsg']!==''){
	    				$errorMsg = $info['errorMsg'];
	    			}else{
	    				$trackingInfo = $info['data'];
	    				if(!empty($info['data']->order_id)){
	    					$info['data']->addi_info =str_replace("`",'"',$info['data']->addi_info);
	    					$addi_info = json_decode($info['data']->addi_info,true);
	    					$layout = (!empty($addi_info['layout']))?$addi_info['layout']:1;
	    					$recom_prod_count = (!empty($addi_info['recom_prod_count']))?$addi_info['recom_prod_count']:$recom_prod_count;
	    					$recom_prod_group = (!empty($addi_info['recom_prod_group']))?$addi_info['recom_prod_group']:$recom_prod_group;
	    					$consignee_country_code = (!empty($addi_info['consignee_country_code']))?$addi_info['consignee_country_code']:'EN';
	    					//获取订单信息
	    					$orderInfo= OdOrder::find()->where(['order_source_order_id'=>$info['data']->order_id])->asArray()->one();
	    					$order_platform = $info['data']->platform;
	    					$seller_id = empty($info['data']->seller_id)?'':$info['data']->seller_id;
	    					$site_id = empty($orderInfo['order_source_site_id'])?'':$orderInfo['order_source_site_id'];
	    					//print_r($orderInfo);
	    					if($orderInfo<>null){//获取订单items
	    						$orderItems = OdOrderItem::find()
		    						->where(['order_id'=>$orderInfo['order_id']])
		    						->asArray()->all();
	    						//存在该订单时，检测订单对应的卖家及对应平台的推荐商品是否ready
	    						if(empty($isCheck)){
	    							//获取自定义推荐商品
	    							$CustomizedRecommended = OrderTrackingMessageHelper::getTargetCustomizedRecommendedProds($puid,$order_platform, $seller_id,$recom_prod_group,$recom_prod_count);
	    							//无自定义推荐商品时，用旧方法获取系统自动生成的
	    							if(empty($CustomizedRecommended['prods'])){
	    								$isReady = true;
	    								$CustomizedRecommended=[];
	    							}
	    						}else 
	    							$CustomizedRecommended = OrderTrackingMessageHelper::getTargetCustomizedRecommendedProds($puid,$order_platform, $seller_id,$recom_prod_group,$recom_prod_count);
	    					}
	    				}
	    			}
		    	}
		    	elseif(count($parmas)==3 && $parmas[2]==1){
		    		$puid = $parmas[0];
		    		$order_id = $parmas[1];
		    		//获取订单和物流信息
		    		$info = TrackingMsgHelper::getOrderAndTrackingInfo($puid, $order_id);
		    		
		    		if($info['errorMsg']!==''){
		    			$errorMsg = $info['errorMsg'];
		    		}else{
		    			if(!empty($info['order'])){
		    				$orderInfo = $info['order'];//获取订单信息
		    				$seller_id = $info['order']['selleruserid'];
		    				$order_platform = $info['order']['order_source'];
		    				$site_id = empty($info['order']['order_source_site_id'])?'':$info['order']['order_source_site_id'];
		    				$consignee_country_code = (!empty($info['order']['consignee_country_code']))?$info['order']['consignee_country_code']:'EN';
		    				 
		    				$info['order']['addi_info'] = str_replace("`",'"',$info['order']['addi_info']);
		    				$addi_info = json_decode($info['order']['addi_info'],true);
		    				if(!empty($addi_info)){
		    					$layout = (!empty($addi_info['layout']))?$addi_info['layout']:1;
		    					$recom_prod_count = (!empty($addi_info['recom_prod_count']))?$addi_info['recom_prod_count']:$recom_prod_count;
		    					$recom_prod_group = (!empty($addi_info['recom_prod_group']))?$addi_info['recom_prod_group']:$recom_prod_group;
		    				}
		    				
		    				$orderItems = OdOrderItem::find()
		    					->where(['order_id'=>$orderInfo['order_id']])
		    					->asArray()->all();
		    			}
		    			
		    			if(!empty($info['data'])){
		    				$trackingInfo = $info['data'];//获取物流信息
		    			}
		    			
		    			if(empty($isCheck)){
		    				//获取自定义推荐商品
		    				$CustomizedRecommended = OrderTrackingMessageHelper::getTargetCustomizedRecommendedProds($puid,$order_platform, $seller_id,$recom_prod_group,$recom_prod_count);
		    				//无自定义推荐商品时，用旧方法获取系统自动生成的
		    				if(empty($CustomizedRecommended['prods'])){
		    					$isReady = true;
		    					$CustomizedRecommended=[];
		    				}
		    			}else{
		    				//获取自定义推荐商品
		    				$CustomizedRecommended = OrderTrackingMessageHelper::getTargetCustomizedRecommendedProds($puid,$order_platform, $seller_id,$recom_prod_group,$recom_prod_count);
		    			}
		    		}
		    		
		    		
		    	}
    		}
    	}
    	//获取订单目的地国家语言及前端html固定显示文字的对应语言版本
		$TRANSLATE_MAPPING = TrackingMsgHelper::getTranslateMapping();
		$lang = TrackingMsgHelper::getToNationLanguage($consignee_country_code);
    	if( isset($TRANSLATE_MAPPING[$lang])){
    		$translate_contents = $TRANSLATE_MAPPING[$lang];
    	}else 
    		$translate_contents = $TRANSLATE_MAPPING['EN'];
    	
    	//获取订单起运地及目的地的信息
    	$originCountry='';
    	$destinationCountry='--';
    	if(!empty($trackingInfo->from_nation)){
    		$country = SysCountry::findOne(['country_code'=>$trackingInfo->from_nation]);
    		$originCountry = $country->country_en;
    	}
    	
    	if(!empty($trackingInfo->to_nation) && $trackingInfo->to_nation!=='--'){	
    	}else{
    		$trackingInfo->to_nation=$consignee_country_code;
    	}
    	
    	$country = SysCountry::findOne(['country_code'=>$trackingInfo->to_nation]);
    	if (empty($country)){
    		$trackingInfo->to_nation = $trackingInfo->getConsignee_country_code();
    		$country = SysCountry::findOne(['country_code'=>$trackingInfo->to_nation ]);
    	}

    	$destinationCountry = !empty($country->country_en)?$country->country_en:$trackingInfo->getConsignee_country_code() ;
    	
    	//如果isReady
		if($isReady){
			//获取推荐商品信息
			if(!empty($CustomizedRecommended['prods'])){
				$recommendProduct = $CustomizedRecommended['prods'];
			}else{
				$recommends = MessageApiHelper::getRecomProductsFor($info['data']->platform,$info['data']->seller_id,$orderInfo['order_source_site_id'],$recom_prod_count);
				if(count($recommends)>0){
					foreach ($recommends as $recommend){
						$recommendProduct[]=$recommend;
					}
				}
			}
				
			//记录展示次数信息到ut_config
			if($order_platform !== ''){
				$today = date('Y-m-d');
		    	$todayShowCount = ConfigHelper::getConfig("Recommend_prod_browse_count_".$today,'NO_CACHE');
		    	if($todayShowCount==null){//该日尚无展示记录，则新建记录并count=1
		    		$todayShowCount = [$order_platform=>1];
		    		ConfigHelper::setConfig("Recommend_prod_browse_count_".$today, json_encode($todayShowCount));
				}
		    	else{
		    		$todayShowCount = json_decode($todayShowCount,true);
		    		if(empty($todayShowCount)){//该日有记录单记录为空
		    			$todayShowCount = [$order_platform=>1];
		    			ConfigHelper::setConfig("Recommend_prod_browse_count_".$today, json_encode($todayShowCount));
		    		}else{//该日有记录且不为空
		    			if(isset($todayShowCount[$order_platform])){
		    				$todayShowCount[$order_platform] =intval($todayShowCount[$order_platform])+1;
		    			}else{
		    				$todayShowCount[$order_platform] = 1;
		    			}
		    			ConfigHelper::setConfig("Recommend_prod_browse_count_".$today, json_encode($todayShowCount));
		    		}
		    	}
	    	}
	    	//render page
	        return $this->renderAjax('index',[
	        					'trackingInfo'=>$trackingInfo,
	        					'orderInfo'=>$orderInfo,
	        					'orderItems'=>$orderItems,
	        					'recommendProduct'=>$recommendProduct,
	        					'recom_prod_count'=>$recom_prod_count,
	        					'recom_prod_group'=>$recom_prod_group,
	        					'layout'=>$layout,
	        					'errorMsg'=>$errorMsg,
	        					'puid'=>(isset($puid))?$puid:'',
	        					'translate_contents'=>$translate_contents,
	        					'originCountry'=>$originCountry,
	        					'destinationCountry'=>$destinationCountry,
	        					'isReady'=>$isReady,
				        		'seller_id'=>$seller_id,
				        		'site_id'=>$site_id,
				        		'platform'=>$order_platform
	        				]);
		}else{//isReady=false
			//generate Recommend Products
			/*
			TrackingRecommendProductHelper::generateTempRecommendProducts($order_platform,$seller_id,$site_id);
			*/
			//render page
			return $this->renderAjax('index',[
					'trackingInfo'=>$trackingInfo,
					'orderInfo'=>$orderInfo,
					'orderItems'=>$orderItems,
					'recommendProduct'=>$recommendProduct,
					'recom_prod_count'=>$recom_prod_count,
					'recom_prod_group'=>$recom_prod_group,
					'layout'=>$layout,
					'errorMsg'=>$errorMsg,
					'puid'=>(isset($puid))?$puid:'',
					'translate_contents'=>$translate_contents,
					'originCountry'=>$originCountry,
					'destinationCountry'=>$destinationCountry,
					'isReady'=>$isReady,
					'seller_id'=>$seller_id,
					'site_id'=>$site_id,
					'platform'=>$order_platform
					]);
			
		}
    }
    
    /**
     * 页面展示的推荐产品life_view_count +1
     */
    public function actionAddViewCount()
    {
    	$result['success'] = true;
    	$result['message'] = 'No product need to add view count.';
    	if(isset($_GET['puid']) && is_numeric($_GET['puid'])){
    		$puid = $_GET['puid'];
	    	if(isset($_GET['ids'])){
	    		$id_arr=array();
	    		$ids = explode(',', $_GET['ids']);
	    		foreach ($ids as $id){
	    			$id=trim($id);
	    			if($id!=='' && is_numeric($id)){
	    				$id_arr[]=$id;
	    			}
	    		}
	    		
	    		$result=TrackingMsgHelper::addViewCountByIds($puid,$id_arr);
	    	}else{
	    		$result['success'] = false;
	    		$result['message'] = 'Ids lost.';
	    	}
		}else{
			$result['success'] = false;
			$result['message'] = 'Puid lost.';
		}
    	exit( json_encode($result) );
    }
    
    /**
     * 页面展示的推荐产品life_click_count +1
     */
    public function actionAddClickCount()
    {
    	$result['success'] = true;
    	$result['message'] = 'No product need to add click count.';
    	
    	if(isset($_GET['puid']) && is_numeric($_GET['puid'])){
    		$puid = $_GET['puid'];
    		if(isset($_GET['id'])){
    			$id=trim($_GET['id']);
    			if($id!=='' && is_numeric($id)){
    				$result=TrackingMsgHelper::addClickCountById($puid,$id);
    			}
    		}else{
	    		$result['success'] = false;
	    		$result['message'] = 'Id lost.';
	    	}
    	}else{
			$result['success'] = false;
			$result['message'] = 'Puid lost.';
		}
    	exit( json_encode($result) );
    }
    
    public function actionTestAutoRules()
    {
    	$platform = $_GET['platform'];
    	$account = $_GET['account'];
    	$nation = $_GET['nation'];
    	$status = $_GET['status'];
    	$rtn = MessageHelper::getTopTrackerAuotRuleName($platform, $account, $nation,$status);
    	exit($rtn);
    }
    
    public function actionTest(){
    	$str='MTI5LTM1MjQtMWE2ZS0w';
    	$parmaStr = MessageHelper::decryptBuyerLinkParam($str);
    	var_dump($parmaStr);
    }
}
