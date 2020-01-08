<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\catalog\helpers;
use yii;
use yii\base\Exception;
use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\catalog\models\Product;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\models\SaasEbayUser;
use Qiniu\json_decode;
use common\helpers\Helper_Array;
use eagle\modules\catalog\models\ProductAliases;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\AliexpressListing;
use eagle\modules\listing\models\AliexpressListingDetail;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\helpers\WishHelper;
use eagle\models\SaasWishUser;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\listing\models\LazadaListingV2;
use eagle\models\SaasLazadaUser;
use eagle\modules\listing\models\AliexpressCategory;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class MatchingHelper {
	
    static public function getLeftMenuTree(){
    	$menu = [
	    	TranslateHelper::t ( '从订单配对' ) => [
		    	'icon' => 'icon-stroe',
		    	'items' => [
			    	TranslateHelper::t ( '现有订单' ) => [
			    		'url' => './index'
	    			]
    			]
    		],
    		TranslateHelper::t ( '从产品配对' ) => [
    			'icon' => 'icon-stroe',
    			'items' => [
    				TranslateHelper::t ( 'eBay' ) => [
    					'url' => './product-list?platform=ebay'
    				],
    				TranslateHelper::t ( 'AliExpress' ) => [
    					'url' => './product-list?platform=aliexpress'
    				],
    				TranslateHelper::t ( 'Wish' ) => [
    					'url' => './product-list?platform=wish'
    				],
    				TranslateHelper::t ( 'Cdiscount' ) => [
    					'url' => './product-list?platform=cdiscount'
    				],
    				TranslateHelper::t ( 'Lazada' ) => [
    					'url' => './product-list?platform=lazada'
    				],
    				TranslateHelper::t ( 'Linio' ) => [
    				    'url' => './product-list?platform=linio'
    				],
    				TranslateHelper::t ( 'Jumia' ) => [
	    				'url' => './product-list?platform=jumia'
    				],
    			]
    		],
    	];
    
    	return $menu;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询可配对的订单信息
     +---------------------------------------------------------------------------------------------
     * @param  $params   array()     条件
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getMatchingOrderInfo($params){
    	//绑定的selleruseid
    	$selleruserid = [];
    	$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
    	foreach ($platformAccountInfo as $p_key=>$p_v){
    		if(!empty($p_v)){
    			foreach ($p_v as $s_key=>$s_v){
    				$selleruserid[] = $s_key;
    			}
    		}
    	}
    	
    	$params['per-page'] = empty($params['per-page']) ? 20 : $params['per-page'];
    	$params['page'] = empty($params['page']) ? 0 : $params['page'] - 1;
    	
    	//符合信息的所有订单
    	$orders = self::GetCheckOrderInfo($params);
    	
    	//查询Item信息
    	$query = self::GetCheckItemInfo($orders, $params);
//     	$tmpCommand = $query->createCommand();
//         echo "<br>".$tmpCommand->getRawSql();
    	$pagination = new Pagination([
    	        'page' => $params['page'],
    			'pageSize' => $params['per-page'],
    			'totalCount' => $query->count(),
    			'pageSizeLimit' => [20,200],
    			]);
    	$result['pagination'] = $pagination;
    	
    	$items = $query
    		->limit($pagination->limit)
    		->offset($pagination->offset)
    		->asArray()
    		->all();
    	
    	//整理显示信息
    	$user=\Yii::$app->user->identity;
		$puid = $user->getParentUid();
		
		//listing 站点前缀
		$siteEbayUrl = \common\helpers\Helper_Siteinfo::getSiteViewUrl();
		$siteEbayList = \common\helpers\Helper_Siteinfo::getSite();
		
    	$order_list = array();
    	foreach ($items as $item){
    		if(!empty($orders[$item['order_id']])){
	    		$order = $orders[$item['order_id']];
	    		$one = array();
	    	    $one = [
	    	        'order_id' => ltrim($order['order_id'], '0'),
	    	        'platform_order_no' => $order['order_source_order_id'],
	    	        'order_source' => $order['order_source'],
	    	        'selleruserid' => $order['selleruserid'],
	    	        'currency' => $order['currency'],
	    	        'sku' => $item['sku'],
	    	        'root_sku' => $item['root_sku'],
	    	        'product_name' => $item['product_name'],
	    	        'quantity' => $item['quantity'],
	    	        'price' => $item['price'],
	    	        'order_item_id' => $item['order_item_id'],
	    	        'matching_pending' => '',
	    	        'matching_status' => 1,
	    	    ];
    		    
    		    //产品属性
    		    $one['product_attributes_arr'] = \eagle\modules\order\helpers\OrderListV3Helper::getProductAttributesByPlatformItem($order['order_source'], $item['product_attributes']);
    		    //产品URL
    		    $product_name_url = '';
    		    switch ($order['order_source']){
    		    	case 'ebay':
    		    		$product_name_url = in_array($order['order_source_site_id'],$siteEbayList)?$siteEbayUrl[$order['order_source_site_id']].$item['order_source_itemid']:$item['product_url'];
    		    		break;
    		    	case 'aliexpress':
    		    		$product_name_url = "https://www.aliexpress.com/item/xxx/".$item['order_source_itemid'].".html";
    		    		break;
    		    	case 'amazon':
    		    		$tmpurl = "http://www.amazon.";
    		    		$tmpplace=strtolower($order['order_source_site_id']);
    		    		if ($tmpplace=='jp'||$tmpplace=='uk') {
    		    			$tmpurl .='co.'.$tmpplace;
    		    		}else if ($tmpplace=='mx'||$tmpplace=='br'||$tmpplace=='au') {
    		    			$tmpurl .='com.'.$tmpplace;
    		    		}else if ($tmpplace=='us') {
    		    			$tmpurl .='com';
    		    		}else{
    		    			$tmpurl .=$tmpplace;
    		    		}
    		    		$tmpurl .= "/gp/product/".$item['order_source_itemid'];
    		    
    		    		$product_name_url = $tmpurl;
    		    		break;
    		    	default:
    		    		$product_name_url = $item['product_url'];
    		    }
    		    $one['product_name_url'] = $product_name_url;
    		    //图片url
    		    $photo_primary_url = $item['photo_primary'];
    		    if($order['order_source']=='cdiscount'){
    		    	$it = OdOrderItem::findOne(['order_item_id' => $item['order_item_id']]);
    		    	$photo_primary_url = \eagle\modules\order\helpers\CdiscountOrderInterface::getCdiscountOrderItemPhotoForBrowseShow($it,$puid);
    		    }
    		    else if($order['order_source']=='priceminister')
    		    	$photo_primary_url =\eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item['photo_primary'], $puid, 1);
    		    else if(in_array($order['order_source'],['lazada','linio','jumia'])){
    		    	if(!empty($photo_primary_url)){
    		    		$big_photo_primary_url = str_replace("-catalog", "", $photo_primary_url);// 去掉 -catalog
    		    	}
    		    }else if($order['order_source']=='amazon'){
    		    	$big_photo_primary_url = str_replace("160_.jpg","500_.jpg",$photo_primary_url);
    		    }
    		    $one['photo_primary_url'] = $photo_primary_url;
    		    $one['big_photo_primary_url'] = empty($big_photo_primary_url) ? $photo_primary_url : $big_photo_primary_url;
    		    //配对状态信息
    		    if(!empty($item['root_sku'])){
    		    	$one['matching_status'] = 3;
    		    }
    		    else{
    		    	if(!empty($item['addi_info'])){
    		    		$addi_info = json_decode($item['addi_info'], true);
    		    		if(!empty($addi_info['matching_pending'])){
    		    			$one['matching_pending'] = $addi_info['matching_pending'];
    		    			$one['matching_status'] = 2;
    		    		}
    		    	}
    		    }
    		    
    		    $order_list[] = $one;
    		}
    	}
    	//print_r($order_list);die;
    	$result['data'] = $order_list;
    	
    	return $result;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询可配对的已付款、待上传订单Order信息
     +---------------------------------------------------------------------------------------------
     * @param  $params   array()     条件
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function GetCheckOrderInfo($params = array()){
        $params['start_date'] = empty($params['start_date']) ? '' : strtotime($params['start_date']);
        if(!empty($params['end_date'])){
        	//期末时间增加一天
        	$end_date = $params['end_date'];
        	$params['end_date'] = strtotime("$end_date +1 day");
        }
        else
        	$params['end_date'] = '';
        $params['order_source'] = empty($params['platform']) ? '' : $params['platform'];
        unset($params['platform']);
        
    	$query = OdOrder::find()->select(['order_id', 'order_source_order_id', 'order_source', 'selleruserid', 'currency', 'order_source_site_id']);
    	$query = $query
	    	->where("(od_order_v2.order_status >='".OdOrder::STATUS_PAY."' and od_order_v2.order_status <'".OdOrder::STATUS_SHIPPED."') or od_order_v2.order_status='".OdOrder::STATUS_SUSPEND."' or od_order_v2.order_status='".OdOrder::STATUS_OUTOFSTOCK."' ")
    	    ->andwhere(['order_relation'=>['normal' , 'sm', 'fs', 'ss']]); //'carrier_step' => ['0', '4', '5']
    	
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'start_date':
    				$query->andWhere("`order_source_create_time` is null or `order_source_create_time`>=$value");
    				break;
    			case 'end_date':
    				$query->andWhere("`order_source_create_time` is null or `order_source_create_time`<=$value");
    				break;
    			case 'selleruserid':
			    case 'order_source':
    			    $query->andWhere([$key=>$value]);
    			    break;
    			default:
    				break;
    		}
    	}
    	
    	//只显示已绑定的账号的信息
    	$bind_stores = '';
    	//$uid = \Yii::$app->subdb->getCurrentPuid();
    	//$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($uid);
    	$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
    	foreach ($platformAccountInfo as $p_key=>$p_v){
    		if(!empty($p_v)){
    			foreach ($p_v as $s_key=>$s_v){
    				$bind_stores[] = $s_key;
    			}
    		}
    	}
    	if($bind_stores != ''){
    		$query->andWhere(['in','selleruserid',$bind_stores]);
    	}
    	
    	$data = $query->asArray()->all();
    	 
    	$orders = array();
    	foreach ($data as $order){
    		$orders[ $order['order_id']] = $order;
    	}
    	unset($data);
    	
    	return $orders;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	过滤指定订单Item信息，返回$query
     +---------------------------------------------------------------------------------------------
     * @param  $orders   array()     订单信息集合
     * @param  $params   array()     条件
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function GetCheckItemInfo($orders, $params = array()){
    	$order_ids = array();
    	foreach ($orders as $order){
    		$order_ids[] = $order['order_id'];
    	}
    	
    	$query = OdOrderItem::find()->select(['order_id', 'sku', 'root_sku', 'product_name', 'quantity', 'price', 'order_item_id', 'order_source_itemid', 'product_attributes', 'photo_primary', 'product_url', 'addi_info'])
    		->where(['order_id' => $order_ids]);
    	foreach ($params as $key=>$value){
    	    $value = trim($value);
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'matching_type':
    				switch ($value){
    					case 1:
    						$query->andWhere("(root_sku is null || root_sku='') && (addi_info is null || addi_info not like '%matching_pending\":%' || addi_info like '%matching_pending\":[]%')");
    						break;
    					case 2:
    						$query->andWhere("(root_sku is null || root_sku='') && (addi_info is not null && addi_info like '%matching_pending\":%' && addi_info not like '%matching_pending\":[]%')");
    						break;
    					case 3:
    						$query->andWhere("root_sku is not null && root_sku<>''");
    						break;
    					//所有未配对的
						case 4:
							$query->andWhere("root_sku is null || root_sku=''");
							break;
    				}
    				break;
    			case 'matching_searchval':
    			    if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    			        // $query->andWhere("product_name like '%".trim($value)."%'");
    			        $query->andWhere(['like','product_name', $value]);
    			    }
    			    else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'root_sku'){
    			        // $query->andWhere("root_sku like '%".trim($value)."%'");
    			        $query->andWhere(['like','root_sku', $value]);
    			    }
    			    else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'order_id'){
    			        // $query->andWhere("order_id=".trim($value));
    			        $query->andWhere(['order_id'=>$value]);
    			    }
    			    else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'order_source_order_id'){
    			        // $query->andWhere("order_source_order_id='".trim($value)."'");
    			        $query->andWhere(['order_source_order_id'=>$value]);
    			    }
    			    else{
    			        // $query->andWhere("sku like '%".trim($value)."%'");
    			        $query->andWhere(['like','sku', $value]);
    			    }
    			    break;
    			case 'is_not_matching':
    				if($value == 1){
    					$query->andWhere("root_sku is null || root_sku=''");
    				}
    				break;
    			default:
    				break;
    		}
    	}
    	$query->orderBy("order_id");
    	
    	return $query;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	检测已付款、发货中的订单，根据别名表，自动配对
     +---------------------------------------------------------------------------------------------
     * @param  
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function RefreshMatchingOrder(){
    	$result['ack'] = true;
    	$result['msg'] = '';
    	try{
	        $params['matching_type'] = 4;
	        //符合信息的所有订单
	        $orders = self::GetCheckOrderInfo();
	         
	        //查询需检测的订单Item信息
	        $query = self::GetCheckItemInfo($orders, $params);
	        $items = $query->asArray()->all();
	        
	        $updateSKUList = array();   //需要更新待发货数量的本地SKU组
	        $fullName = \Yii::$app->user->identity->getFullName();
	        foreach($items as $item){
	        	if(!empty($item['sku']) && empty($item['root_sku'])){
	                $platform = '';
	                $selleruserid = '';
	                if(!empty($orders[$item['order_id']])){
	                	$platform = $orders[$item['order_id']]['order_source'];
	                	$selleruserid = $orders[$item['order_id']]['selleruserid'];
	                }
	                $sku = $item['sku'];
	                $root_sku = ProductApiHelper::getRootSKUByAlias($sku, $platform, $selleruserid);
	                $itemid = ltrim($item['order_item_id'],'0');
	                if(!empty($root_sku)){
	                    $log=array($itemid,$root_sku,false,$fullName);
	                    OperationLogHelper::batchInsertLog('order', $log, '配对SKU-1','['.$sku.']配对了['.$root_sku.']');
	                    $rt=OrderUpdateHelper::saveItemRootSKU($itemid,$root_sku,false , $fullName ,'sku配对');
	                    if($rt['ack'] == false){
	                    	$result['ack'] = false;
	                    	$result['msg'] .= '配对：itemid: '.$itemid.', sku: '.$sku.', root_sku: '.$root_sku.', msg: '.$rt['message']."\n";
	                    }
	                    else{
	                        $updateSKUList[] = $root_sku;
	                    }
	                }
	            }
	        }
	        
	        //更新待发货数量
	        if(!empty($updateSKUList)){
	            $rt = \eagle\modules\inventory\helpers\WarehouseHelper::RefreshSomeQtyOrdered($updateSKUList);
	            
	            if ($rt['status'] == 0){
	                $result['ack'] = false;
	                $result['msg'] .= "order of batch to update ordered_qty is error: ".json_encode($updateSKUList)."\n";
	            }
	        }
    	}
    	catch(\Exception $ex){
    		$result['ack'] = false;
    		$result['msg'] .= $ex->getMessage()."\n";
    	}
    	
    	//写入错误信息
    	if($result['ack'] == false){
    		\Yii::info("RefreshMatchingOrder err: ".$result['msg'], "file");
    	}
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	根据识别类型，识别可配对的root_sku，并保存到order_item的addi_info
     +---------------------------------------------------------------------------------------------
     * @param  $params_AM   array()     识别类型条件
     * @param  $params_S    array()     查询有效订单信息条件
     * @param  $type        string      识别类型，空/order为订单，ebay等为对应平台
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function AutomaticMatching($params_AM, $params_S, $type)
    {
    	$ret['status'] = '1';
    	$ret['msg'] = '';
    	
    	if(empty($type) || $type == 'order'){
    		$params_S['is_not_matching'] = 1;
    		//符合信息的所有订单
    		$orders = self::GetCheckOrderInfo($params_S);
    		 
    		//查询需检测的订单Item信息
    		$query = self::GetCheckItemInfo($orders, $params_S);
    		$items = $query->all();
    	}
    	else{
    		$params_S['per-page'] = -1;
    		$params_S['is_not_matching'] = 1;
    		$query = MatchingHelper::getProductInfo($params_S, $type);
    		$items = $query['data'];
    	}
    	
    	$matchingQty = 0;    //已识别数量
    	foreach ($items as $item)
    	{
    		if(empty($type) || $type == 'order'){
    			if(empty($item->sku) || !empty($item->root_sku)){
    				continue;
    			}
    			$sku = $item->sku;
    		}
    		else{
    			if(empty($item['sku']) || !empty($item['root_sku'])){
    				continue;
    			}
    			$sku = $item['sku'];
    		}
    		
    	        //*******整理需识别的SKU******start
   	        //「订单SKU」与「本地SKU」一致
   	        if($params_AM['automaticMType'] == 1){
   	        }
   	        //忽略前缀、后缀的的「订单SKU」与「本地SKU」
   	        else if($params_AM['automaticMType'] == 2)
   	        {
   	        	$startStr = $params_AM['startStr'];
   	        	$endStr = $params_AM['endStr'];
   	        	$startArr = array();
   	        	//判断是否含有分隔符 , ， ; ； 、
   	        	$startArr = preg_split('/[,，;；、]+/', $startStr);
   	        	//截取前缀
   	        	foreach ($startArr as $start){
   	        		if(!empty($start) && strpos($sku, $start) === 0)
  	        		{
   	        			$sku = substr($sku, strlen($start));
   	        			break;
   	        		}
   	        	}
   	        	$endArr = array();
   	        	//判断是否含有分隔符 , ， ; ； 、
   	        	$endArr = preg_split('/[,，;；、]+/', $endStr);
   	        	//截取后缀
   	        	foreach ($endArr as $end){
   	        		if(!empty($end) && substr($sku,-strlen($end))==$end)
   	        		{
   	        			$sku = substr($sku, 0, strlen($sku) - strlen($end));
   	        			break;
   	        		}
   	        	}
   	        }
   	        //截取后的的「订单SKU」与「本地SKU」
   	        else if($params_AM['automaticMType'] == 3)
   	        {
   	        	$startLen = $params_AM['startLen'];
   	        	$endLen = $params_AM['endLen'];
   	        	//起始
   	        	if($startLen == null || $startLen == '')
   	        	{
   	        		$startLen = 1;
   	        	}
   	        	//结束
   	        	if($endLen == null || $endLen == '')
   	        	{
   	        		$endLen = strlen($sku);
   	        	}
   	        	$sku = substr($sku, $startLen - 1, $endLen - $startLen + 1);
   	        }
   	        //*******整理需识别的SKU******end
   	        
   	        //查询符合要求的本地SKU
   	        $pro_list = Product::find()->select(['sku'])->where(['sku' => $sku])->asArray()->all();
   	    	if(!empty($pro_list)){
   	    		$matching_pending = array();
   	    		foreach ($pro_list as $pro){
   	    			$matching_pending[] = $pro['sku'];
   	    		}
   	    		
   	    		if(empty($type) || $type == 'order'){
   	    			$addi_info = array();
   	    			if(!empty($item->addi_info)){
   	    				$addi_info = json_decode($item->addi_info, true);
   	    			}
   	    			$addi_info['matching_pending'] = $matching_pending;
   	    			$item->addi_info = json_encode($addi_info);
   	    			$item->save(false);
   	    			$matchingQty++;
   	    		}
   	    		else{
   	    			$detail = array();
   	    			if($type == 'ebay'){
   	    				$detail = EbayItemDetail::findOne(['itemid' => $item['pro_id']]);
   	    			}
   	    			else if($type == 'aliexpress'){
   	    				$detail = AliexpressListingDetail::findOne(['productid' => $item['pro_id']]);
   	    			}
   	    			else if($type == 'wish'){
   	    				$detail = WishFanben::findOne(['id' => $item['pro_id']]);
   	    			}
   	    			else if($type == 'cdiscount'){
   	    				$detail = CdiscountOfferList::findOne(['id' => $item['pro_id']]);
   	    			}
   	    			else if($type == 'lazada'){
   	    				$detail = LazadaListingV2::findOne(['id' => $item['pro_id']]);
   	    			}
   	    			else if(in_array($type, ['linio', 'jumia'])){
   	    				$detail = LazadaListing::findOne(['id' => $item['pro_id']]);
   	    			}
   	    			 
   	    			if(!empty($detail)){
   	    				$matching_info = array();
   	    				if(!empty($detail->matching_info)){
   	    					$matching_info = json_decode($detail->matching_info, true);
   	    				}
   	    				$matching_info['matching_pending'][$item['sku']] = $matching_pending;
   	    				$detail->matching_info = json_encode($matching_info);
   	    				$detail->save(false);
   	    				$matchingQty++;
   	    			}
   	    		}
   	    	}
	   	}
   
    	$ret['Qty'] = $matchingQty;
    	return $ret;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	现有订单，查询可生成SKU的信息
     +---------------------------------------------------------------------------------------------
     * @param  $params   array()     条件
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function GetCreateProductInfo($params){
        $params_S['is_not_matching'] = 1;
        //符合信息的所有订单
        $orders = self::GetCheckOrderInfo($params);
         
        //查询需检测的订单Item信息
        $query = self::GetCheckItemInfo($orders, $params);
        $items = $query->asArray()->all();
        
        //整理显示信息
        $user=\Yii::$app->user->identity;
        $puid = $user->getParentUid();
        
        //查询已存在商品库的商品
        $skus = array();
        $exists_pro_sku = array();
        foreach($items as $item){
            if(!empty($item['sku']) && !in_array($item['sku'], $skus)){
                $skus[] = $item['sku'];
            }
        }
        $pro = Product::find()->select(['sku'])->where(['sku' => $skus])->asArray()->all();
        foreach($pro as $v){
            if(!in_array($v['sku'], $exists_pro_sku)){
            	$exists_pro_sku[] = $v['sku'];
            }
        }
        
        $sku_info = array();
        foreach($items as $item){
            if(!empty($item['sku']) && !in_array($item['sku'], $exists_pro_sku)){
                if(array_key_exists($item['sku'], $sku_info)){
                    $sku_info[$item['sku']]['matching_itemid'] .= ','.$item['order_item_id'];
                }
                else{
                    $item['photo_primary_url'] = '';
                    $item['matching_itemid'] = $item['order_item_id'];
                    
                    if(!empty($orders[$item['order_id']])){
                    	$order = $orders[$item['order_id']];
                        //图片url
                        $photo_primary_url = $item['photo_primary'];
                        if($order['order_source']=='cdiscount'){
                        	$it = OdOrderItem::findOne(['order_item_id' => $item['order_item_id']]);
                        	$photo_primary_url = \eagle\modules\order\helpers\CdiscountOrderInterface::getCdiscountOrderItemPhotoForBrowseShow($it,$puid);
                        }
                        else if($order['order_source']=='priceminister')
                        	$photo_primary_url =\eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item['photo_primary'], $puid, 1);
                        else if(in_array($order['order_source'],['lazada','linio','jumia'])){
                        	if(!empty($photo_primary_url)){
                        		$big_photo_primary_url = str_replace("-catalog", "", $photo_primary_url);// 去掉 -catalog
                        	}
                        }else if($order['order_source']=='amazon'){
                        	$big_photo_primary_url = str_replace("160_.jpg","500_.jpg",$photo_primary_url);
                        }
                        $item['photo_primary_url'] = $photo_primary_url;
                    }
                    
                    $sku_info[$item['sku']] = $item;
                }
            }
        }
        
        return array_values($sku_info);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	一键创建商品
     +---------------------------------------------------------------------------------------------
     * @param  $params   array()     条件
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/4/26		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function CreateProductInfo($data){
    	$ret['success'] = 1;
    	$ret['msg'] = '';
    	$ret['itemid'] = array();
    	if(empty($data)){
    		return ['success' => 0, 'msg' => '没有可新增的商品信息'];
    	}
    	
    	//判断是否在线商品
    	if(!empty($data['platform'])){
    		$platform = $data['platform'];
    		unset($data['platform']);
    		
    		$search_con = array();
    		if(!empty($data['search_con'])){
    			$search_con = json_decode($data['search_con'], true);
    			unset($data['search_con']);
    		}
    		
    		//整理信息
    		$pro_info = array();
    		foreach($data as $key => $arr){
    			foreach($arr as $num => $val){
    				$pro_info[$num][$key] = $val;
    			}
    		}
    		
    		//获取基本信息
    		$search_con['per-page'] = 100;         //最多一次性支持生成500条
    		$search_con['page'] = 0;
    		$info = MatchingHelper::getProductInfo($search_con, $platform, array(), true);
    		if(!empty($info['data'])){
    			foreach($pro_info as $key => $val){
    				if(isset($val['item_id']) && !empty($info['data'][$val['item_id']])){
    					$detail = $info['data'][$val['item_id']];
    		
    					$pro_info[$key]['order_item_id'] = $detail['pro_id'];
    					$pro_info[$key]['sku'] = $detail['sku'];
    					$pro_info[$key]['photo_primary'] = $detail['photo_primary_url'];
    					$pro_info[$key]['chil_photo_primary'] = empty($detail['chil_photo_primary_url']) ? $detail['photo_primary_url'] : $detail['chil_photo_primary_url'];
    					$pro_info[$key]['attributes'] = $detail['attributes_arr'];
    					$pro_info[$key]['platform'] = $detail['platform'];
    					$pro_info[$key]['selleruserid'] = $detail['selleruserid'];
    					$pro_info[$key]['photo_others'] = empty($detail['photo_others']) ? '' : $detail['photo_others'];
    					$pro_info[$key]['other_attributes'] = empty($detail['other_attributes']) ? '' : $detail['other_attributes'];
    				}
    				else{
    					unset($pro_info[$key]);
    				}
    			}
    		}
    		else{
    			$pro_info = array();
    		}
    	}
    	else{
        	//整理信息
        	$pro_info = array();
        	foreach($data as $key => $arr){
    	        foreach($arr as $num => $val){
    	        	$pro_info[$num][$key] = $val;
    	        }
        	}
    	}
    	
    	$skus = array();
    	//检测商品信息
    	$is_check = true;
    	//判断是否已存在商品库
    	$exist_sku_str = '';
    	foreach ($pro_info as $pro){
    		//变参父商品，不需判断是否存在商品库
    		if(!empty($pro['chli_sku'])){
	    		if(!in_array($pro['chli_sku'], $skus)){
	    			$skus[] = trim($pro['chli_sku']);
	    		}
    		}
    		else if(!empty($pro['root_sku']) && !in_array($pro['root_sku'], $skus)){
    			$skus[] = trim($pro['root_sku']);
    		}
    	}
    	$pro = Product::find()->select(['sku'])->where(['sku' => $skus])->asArray()->all();
    	foreach($pro as $v){
    		$exist_sku_str .= $v['sku'].'<br>';
    	}
    	if($exist_sku_str != ''){
    		$ret['success'] = 0;
    		$ret['error'] = "<span style='color:#ed5466'>以下SKU已存在商品库，不可新增：<br></span>".$exist_sku_str;
    		return $ret;
    	}
    	
    	$uid = \Yii::$app->subdb->getCurrentPuid();
    	//读取常用报关信息
    	$tmpCommonDeclaredInfo = \eagle\modules\carrier\helpers\CarrierOpenHelper::getCommonDeclaredInfoByDefault($uid);
    	
    	if(!empty($tmpCommonDeclaredInfo['id'])){
    		$product_name['ch'] = $tmpCommonDeclaredInfo['ch_name'];
    		$product_name['en'] = $tmpCommonDeclaredInfo['en_name'];
    		$product_name['declared_value'] = $tmpCommonDeclaredInfo['declared_value'];
    		$product_name['declared_weight'] = $tmpCommonDeclaredInfo['declared_weight'];
    		$product_name['detail_hs_code'] = $tmpCommonDeclaredInfo['detail_hs_code'];
    		$product_name['currency'] = 'USD';
    	}
    	//报关信息默认值
   		$product_name['ch'] = empty($product_name['ch']) ? '礼品' : $product_name['ch'];
   		$product_name['en'] = empty($product_name['en']) ? 'gift' : $product_name['en'];
   		$product_name['declared_value'] = empty($product_name['declared_value']) ? '0' : $product_name['declared_value'];
   		$product_name['declared_weight'] = empty($product_name['declared_weight']) ? '50' : $product_name['declared_weight'];
   		$product_name['detail_hs_code'] = empty($product_name['detail_hs_code']) ? '' : $product_name['detail_hs_code'];
   		$product_name['currency'] = empty($product_name['currency']) ? 'USD' : $product_name['currency'];
    	
   		$fullName = \Yii::$app->user->identity->getFullName();
    	//组织商品数据
   		$updateSKUList = array();
   		$new_pro_arr = array();
    	$successQty = 0;
    	$failQty = 0;
    	foreach ($pro_info as $key => $pro){
    		if(count($pro) < 10){
    			continue;
    		}
    		
    		$pro['root_sku'] = empty($pro['root_sku']) ? '' : trim($pro['root_sku']);
    		$pro['sku'] = empty($pro['sku']) ? '' : trim($pro['sku']);
    		$pro['chli_sku'] = empty($pro['chli_sku']) ? '' : trim($pro['chli_sku']);
    		
    		//当是变参商品时，把子产品信息组合一起
    		if(empty($pro['rowspan']) || $pro['rowspan'] > 0){
    			//是否父商品
    			$is_parametron = false;
    			if(!empty($pro['rowspan']) && $pro['rowspan'] > 0){
    				$is_parametron = true;
    			}
    			
    			$new_pro = array();
    			$children = array();
    			$alias = array();
    			
    			if($is_parametron){
    				$new_pro['type'] = 'C';
    				
    				//子产品属性值
    				if(!empty($pro['attributes'])){
    					foreach ($pro['attributes'] as $num => $attr){
    						$num = $num + 1;
    						$arr = explode(': ', $attr);
    						if(!empty(trim($arr[0]))){
    							$children["config_field_$num"] = trim($arr[0]);
    							$children["config_field_value_$num"][] = empty(trim($arr[1])) ? '' : trim($arr[1]);
    						}
    					}
    				}
    				//子产品基本信息
    				$children["photo_primary"][] = empty($pro['chil_photo_primary']) ? '' : $pro['chil_photo_primary'];
    				$children["sku"][] = $pro['chli_sku'];
    				
    				//增加别名关系
    				if(!empty($pro['sku']) && !empty($pro['chli_sku'])){
    					$alias[] = [
    						'sku' => $pro['chli_sku'],
    						'alias_sku' => $pro['sku'],
    						'platform' => empty($pro['platform']) ? '' : $pro['platform'],
    						'selleruserid' => empty($pro['selleruserid']) ? '' : $pro['selleruserid'],
    					];
    				}
    			}
    			else{
    				$new_pro['type'] = 'S';
    				
    				//增加别名关系
    				if(!empty($pro['sku']) && !empty($pro['root_sku'])){
    					$alias[] = [
	    					'sku' => $pro['root_sku'],
	    					'alias_sku' => $pro['sku'],
	    					'platform' => empty($pro['platform']) ? '' : $pro['platform'],
	    					'selleruserid' => empty($pro['selleruserid']) ? '' : $pro['selleruserid'],
    					];
    				}
    			}
    			
    			$new_pro['sku'] = $pro['root_sku'];
    			$new_pro['name'] = $pro['name'];
    			$new_pro['prod_name_ch'] = $pro['name'];
    			$new_pro['prod_name_en'] = $pro['name'];
    			$new_pro['photo_primary'] = empty($pro['photo_primary']) ? 'http://v2.littleboss.com/images/batchImagesUploader/no-img.png' : $pro['photo_primary'];
    			$new_pro['photo_others'] = empty($pro['photo_others']) ? '' : $pro['photo_others'];
    			$new_pro['declaration_ch'] = empty($pro['prod_name_ch']) ? $product_name['ch'] : $pro['prod_name_ch'];
    			$new_pro['declaration_en'] = empty($pro['prod_name_en']) ? $product_name['en'] : $pro['prod_name_en'];
    			$new_pro['declaration_value_currency'] = empty($pro['currency']) ? $product_name['currency'] : $pro['currency'];
    			$new_pro['declaration_value'] = empty($pro['declared_value']) ? $product_name['declared_value'] : $pro['declared_value'];
    			$new_pro['prod_weight'] = empty($pro['declared_weight']) ? $product_name['declared_weight'] : $pro['declared_weight'];
    			$new_pro['battery'] = 'N';
    			$new_pro['platform'] = empty($pro['platform']) ? '' : $pro['platform'];
    			$new_pro['itemid'] = empty($pro['order_item_id']) ? '' : $pro['order_item_id'];
    			$new_pro['detail_hs_code'] = $product_name['detail_hs_code'];
    			$new_pro['other_attributes'] = empty($pro['other_attributes']) ? '' : $pro['other_attributes'];
    			
    			$new_pro_arr[] = [
	    			'order_item_id' => empty($pro['order_item_id']) ? '' : $pro['order_item_id'],
	    			'matching_itemid' => empty($pro['matching_itemid']) ? '' : $pro['matching_itemid'],
	    			'root_sku' => $pro['root_sku'],
	    			'chli_sku' => $pro['chli_sku'],
	    			'sku' => $pro['sku'],
	    			'product' => $new_pro,
	    			'children' => $children,
	    			'alias' => $alias,
    			];
    		}
    		else if($pro['rowspan'] == -1){
    			//添加到最后一个的变参父商品
    			$index = count($new_pro_arr) - 1;
    			$children = $new_pro_arr[$index]['children'];
    			
    			//子产品属性值
   				if(!empty($pro['attributes'])){
   					foreach ($pro['attributes'] as $num => $attr){
   						$num = $num + 1;
   						$arr = explode(': ', $attr);
   						if(!empty(trim($arr[0]))){
   							$children["config_field_value_$num"][] = empty(trim($arr[1])) ? '' : trim($arr[1]);
   						}
   					}
   				}
   				//子产品基本信息
   				$children["photo_primary"][] = empty($pro['chil_photo_primary']) ? '' : $pro['chil_photo_primary'];
   				$children["sku"][] = $pro['chli_sku'];
   				
   				$new_pro_arr[$index]['children'] = $children;
   				
   				//增加别名关系
   				if(!empty($pro['sku']) && !empty($pro['chli_sku'])){
   					$new_pro_arr[$index]['alias'][] = [
	   					'sku' => $pro['chli_sku'],
	   					'alias_sku' => $pro['sku'],
	   					'platform' => $pro['platform'],
	   					'selleruserid' => $pro['selleruserid'],
   					];
   				}
    		}
    	}
    	
    	foreach ($new_pro_arr as $key => $pro){
    		//生成商品
    		$is_success = true;
    		$product = new Product();
    		$isupdate = false;
    		$not_delete_cli = false;   //是否删除子产品关联信息
    		//当变参父商品已存在时，则变更为更新
    		if(!empty($pro['product']['type']) && $pro['product']['type'] == 'C'){
    			$product = Product::findOne(['sku' => $pro['root_sku']]);
    			if(!empty($product)){
    				$isupdate = true;
    				$not_delete_cli = true;
    				
    				$pro['product']['name'] = $product['name'];
    				$pro['product']['prod_name_ch'] = $product['prod_name_ch'];
    				$pro['product']['prod_name_en'] = $product['prod_name_en'];
    			}
    			else{
    				$product = new Product();
    			}
    		}
    		
    		$result = ProductHelper::saveProduct($product, ['tt' => 'add', 'not_delete_cli' => $not_delete_cli, 'Product' => $pro['product'], 'children' => $pro['children']], $isupdate);
    		if(!is_array($result) && $result != true){
    			$ret['msg'][$key + 1][] = $result.'<br>';
    			$is_success = false;
    		}
    		else if(is_array($result)){
    			if(!empty($result['错误'])){
    				$ret['msg'][$key + 1][] = $result['错误'].'<br>';
    			}
    			else{
    				foreach($result as $msg){
    					if(is_array($msg)){
    						foreach($msg as $v){
    							$ret['msg'][$key + 1][] = $v.'<br>';
    						}
    					}
    					else{
    						$ret['msg'][$key + 1][] = $msg.'<br>';
    					}
    				}
    	
    			}
    			$is_success = false;
    		}
    	
    		if ($is_success){
    			$successQty++;
    			$ret['itemid'][] = $pro['order_item_id'];
    			 
    			if(!empty($pro['matching_itemid'])){
    				$itemids = explode(',', $pro['matching_itemid']);
    				foreach($itemids as $itemid){
    					$log=array($itemid, $pro['root_sku'], false,$fullName);
    					OperationLogHelper::batchInsertLog('order', $log, '配对SKU-1','['.$pro['sku'].']配对了['.$pro['root_sku'].']');
    					$rt=OrderUpdateHelper::saveItemRootSKU($itemid, $pro['root_sku'],false , $fullName ,'sku配对');
    					if($rt['ack'] == false){
    						$ret['success'] = 0;
    						$ret['msg'][$key + 1][] .= '配对：itemid: '.$itemid.', sku: '.$pro['sku'].', root_sku: '.$pro['root_sku'].', msg: '.$rt['message'].'<br>';
    					}
    					else{
    						$updateSKUList[] = $pro['root_sku'];
    					}
    				}
    			}
    			
    			//添加别名关系
    			if(!empty($pro['alias'])){
    				foreach($pro['alias'] as $alias){
    					$new_alias = array();
    					$new_alias['alias_sku'][] = $alias['alias_sku'];
    					$new_alias['pack'][] = '1';
    					$new_alias['platform'][] = $alias['platform'];
    					$new_alias['selleruserid'][] = $alias['selleruserid'];
    					$new_alias['comment'][] = '';
    					$new_alias['AliasStatus'][] = 'add';
    					ProductHelper::updateSkuAliases($alias['sku'], $new_alias);
    				}
    				
    			}
    		}
    		else{
    			$ret['success'] = 0;
    			$failQty++;
    		}
    	}
    	
    	//更新待发货数量
    	if(!empty($updateSKUList)){
    		$rt = \eagle\modules\inventory\helpers\WarehouseHelper::RefreshSomeQtyOrdered($updateSKUList);
    	}
    	
    	$ret['successQty'] = $successQty;
    	$ret['failQty'] = $failQty;
    	return $ret;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	平台显示的特殊信息
     *  array(
     *  	col        显示列信息
     *  	sort       排序信息
     *      seach      可搜索信息
     *  )
     +---------------------------------------------------------------------------------------------
     * @param  $platform      string     平台
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/09		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getPersonalityInfo($platform){
    	$col_info = array();
    	$sort_info = array();
    	$search_info = array();
    	$show_name = '';
    	switch ($platform){
    		case 'ebay':
    			$show_name = 'eBay';
    			$col_info = [
	    			'产品ID' => [
		    			'name' => 'pro_id',
		    			'width' => '100px',
	    			],
	    			'店铺' => [
		    			'name' => 'shopname',
		    			'width' => '150px',
	    			],
	    			'图片' => [
		    			'name' => 'photo_primary_url',
		    			'width' => '70px',
	    			],
	    			'标题' => [
		    			'name' => 'product_name',
		    			'width' => '250px',
	    			],
	    			'ParentSKU' => [
		    			'name' => 'ParentSKU',
		    			'width' => '150px',
	    			],
	    			'SKU' => [
		    			'name' => 'sku',
		    			'width' => '150px',
	    			],
    			];
    			$search_info = [
    				'itemid' => '产品ID',
    				'title' => '产品标题',
    			];
    			break;
    		case 'aliexpress':
    			$show_name = 'AliExpress';
   				$col_info = [
	 				'产品ID' => [
	    				'name' => 'pro_id',
	    				'width' => '100px',
	    			],
	    			'店铺' => [
	    				'name' => 'shopname',
	    				'width' => '150px',
	    			],
    				'图片' => [
    					'name' => 'photo_primary_url',
    					'width' => '70px',
    				],
    				'标题' => [
    					'name' => 'product_name',
    					'width' => '250px',
    				],
    				'SKU(商品编码)' => [
	    				'name' => 'sku',
	    				'width' => '150px',
    				],
    			];
    			$search_info = [
	    			'itemid' => '产品ID',
	    			'title' => '产品标题',
    			];
    			break;
    		case 'wish':
    			$show_name = 'Wish';
    			$col_info = [
    				'店铺' => [
		   				'name' => 'shopname',
    					'width' => '150px',
    				],
    				'图片' => [
    					'name' => 'photo_primary_url',
    					'width' => '70px',
    				],
    				'标题' => [
    					'name' => 'product_name',
    					'width' => '250px',
    				],
    				'ParentSKU' => [
	    				'name' => 'ParentSKU',
	    				'width' => '150px',
    				],
    				'SKU' => [
	    				'name' => 'sku',
	    				'width' => '150px',
    				],
    				
    			];
    			$search_info = [
	    			'title' => '产品标题',
    			];
    			break;
    		case 'cdiscount':
    			$show_name = 'Cdiscount';
    			$col_info = [
   		 			'店铺' => [
    					'name' => 'shopname',
    					'width' => '150px',
    				],
 	   				'图片' => [
    					'name' => 'photo_primary_url',
    					'width' => '70px',
    				],
    				'标题' => [
    					'name' => 'product_name',
    					'width' => '250px',
    				],
    				'SKU' => [
    					'name' => 'sku',
    					'width' => '150px',
    				],
    			 
    			];
    			$search_info = [
    				'title' => '产品标题',
    			];
    			break;
    		case 'lazada':
    			$show_name = 'Lazada';
    			$col_info = [
    				'店铺' => [
		    			'name' => 'shopname',
    					'width' => '150px',
    				],
    				'图片' => [
    					'name' => 'photo_primary_url',
    					'width' => '70px',
    				],
    				'标题' => [
    					'name' => 'product_name',
    					'width' => '250px',
    				],
    				'SKU' => [
    					'name' => 'sku',
    					'width' => '150px',
    				],
    			 
    			];
    			$search_info = [
    				'title' => '产品标题',
    			];
    			break;
    		case 'jumia':
    			$show_name = 'Jumia';
    			$col_info = [
    				'店铺' => [
	    				'name' => 'shopname',
    					'width' => '150px',
    				],
    				'图片' => [
    					'name' => 'photo_primary_url',
    					'width' => '70px',
    				],
    				'标题' => [
    					'name' => 'product_name',
    					'width' => '250px',
    				],
    				'ParentSKU' => [
	    				'name' => 'ParentSKU',
	    				'width' => '150px',
    				],
    				'SKU' => [
    					'name' => 'sku',
    					'width' => '150px',
    				],
    			
    			];
    			$search_info = [
    				'title' => '产品标题',
    			];
    			break;
    		case 'linio':
    			$show_name = 'Linio';
    			$col_info = [
    				'店铺' => [
	    				'name' => 'shopname',
	    				'width' => '150px',
    				],
    				'图片' => [
	    				'name' => 'photo_primary_url',
	    				'width' => '70px',
    				],
    				'标题' => [
	    				'name' => 'product_name',
	    				'width' => '250px',
    				],
    				'SKU' => [
	    				'name' => 'sku',
	    				'width' => '150px',
    				],
    				 
    			];
    			$search_info = [
    				'title' => '产品标题',
    			];
    			break;
    	}
    
    	$personality_info = [
    		'col' => $col_info,
    		'sort' => $sort_info,
    		'serach' => $search_info,
    		'show_name' => $show_name,
    	];
    	return $personality_info;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	商品状态
     +---------------------------------------------------------------------------------------------
     * @param  $platform      string     平台
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/09		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductStatus($platform){
    	$product_status_arr = array();
    	switch ($platform){
    		case 'ebay':
    			$product_status_arr =[ 
    				'on_line_name' => '产品状态',
	    			'on_line' => [
	    				'1' => '在线',
	    				'2' => '下架',
	    			],
    			];
    			break;
   			case 'aliexpress':
   				$product_status_arr = [
   					'on_line_name' => '产品状态',
   					'on_line' => [
		   				'3' => '审核中',
	   					'4' => '审核不通过',
	   					'2' => '已下架',
	   					'1' => '正在销售',
	   				],
   				];
   				break;
 			case 'wish':
  				$product_status_arr = [
  					'on_line_name' => '产品状态',
  					'on_line' => [
	   					'1' => '在线',
	   					'2' => '已下架',
	   				],
	   				'check_name' => '审核状态',
	   				'check' => [
	   					'7' => '待审核',
	   					'8' => '已批准',
	   					'9' => '被拒绝',
	   				],
   				];
   				break;
   			case 'cdiscount':
   				$product_status_arr =[
   					'on_line_name' => '产品状态',
	   				'on_line' => [
	   					'1' => '在售',
	   					'2' => '非在售',
	   				],
   				];
   				break;
   			case 'lazada':
   				$product_status_arr =[
   					'on_line_name' => '商品状态',
   					'on_line' => [
   						'1' => '在线',
   						'2' => '下架',
   						'3' => '已删除',
   					],
   					'check_name' => '产品QC状态',
   					'check' => [
	   					'approved' => 'Approved',
	   					'rejected' => 'Rejected',
	   					'pending' => 'Pending',
   					],
   				];
   				break;
 			case 'linio':
  				$product_status_arr =[
   					'on_line_name' => '商品状态',
   					'on_line' => [
   					    '1' => '在线',
   				    	'2' => '下架',
   				    	'3' => '已删除',
   					],
   				];
   				break;
   			case 'jumia':
   				$product_status_arr =[
   					'on_line_name' => '商品状态',
   					'on_line' => [
	   					'1' => '在线',
	   					'2' => '下架',
   					],
   				];
   				break;
    	}
    
    	return $product_status_arr;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询平台商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params            array()               条件
     * @param  $platform          string                平台
     * @param  $selleruser_info   array(id => name)     店铺信息
     * @param  $is_create_info    bool                  判断是否查询一键生成商品信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/04		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo($params, $platform = 'ebay', $selleruser_info = [], $is_create_info = false){
    	$per_page = empty($params['per-page']) ? 20 : $params['per-page'];
    	$page = empty($params['page']) ? 0 : $params['page'] - 1;
    	
    	$params['per-page'] = empty($params['per-page']) ? 20 : $params['per-page'];
    	$params['page'] = empty($params['page']) ? 0 : $params['page'] - 1;
    	$params['product_status'] = isset($params['product_status']) ? $params['product_status'] : 1;
    	
    	if(empty($selleruser_info)){
    		$selleruser_info = [];
    		$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
    		foreach ($platformAccountInfo as $p_key=>$p_v){
    			if($p_key == $platform && !empty($p_v)){
    				foreach ($p_v as $s_key=>$s_v){
    					$selleruser_info[$s_key] = $s_v;
    				}
    				break;
    			}
    		}
    	}
    	
   		$pro_list = array();
    	$skus = array();
	   	if($platform == 'ebay'){
	    	$pro_list = self::getProductInfo_ebay($params, $skus, $selleruser_info);
    	}
    	else if($platform == 'aliexpress'){
    		$pro_list = self::getProductInfo_aliexpress($params, $skus, $selleruser_info);
    	}
    	else if($platform == 'wish'){
    		$pro_list = self::getProductInfo_wish($params, $skus, $selleruser_info);
    	}
    	else if($platform == 'cdiscount'){
    		$pro_list = self::getProductInfo_cdiscount($params, $skus, $selleruser_info);
    	}
    	else if($platform == 'lazada'){
    		$pro_list = self::getProductInfo_lazada($params, $skus, $selleruser_info);
    	}
    	else if($platform == 'linio'){
    		$pro_list = self::getProductInfo_linio($params, $skus, $selleruser_info);
    	}
    	else if($platform == 'jumia'){
    		list($pro_list, $pagination) = self::getProductInfo_jumia($params, $skus, $selleruser_info, $is_create_info);
    	}
    	
    	if(in_array($platform, ['jumia'])){
    		//重新索引
    		$pro_list = array_values($pro_list);
    		
    		//查询已存在商品库的sku
    		$exist_product_sku = array();
    		$prod = Product::find()->select(['sku'])->where(['sku' => $skus])->asArray()->all();
    		foreach ($prod as $p){
    			$exist_product_sku[strtolower($p['sku'])] = $p['sku'];
    		}
    		//查询所有的配对关系
    		$aliasList = array();
    		$aliasInfo = ProductAliases::find()->where(['alias_sku' => $skus])->orderBy('platform desc, selleruserid desc')->asArray()->all();
    		foreach($aliasInfo as $alias){
    			$aliasList[strtolower($alias['alias_sku'])][] = [
	    			'sku' => $alias['sku'],
	    			'platform' => $alias['platform'],
	    			'selleruserid' => $alias['selleruserid'],
    			];
    		}
    		
    		foreach ($pro_list as &$pro){
    			//整理配对信息
    			if(!empty($pro['sku'])){
    				$sku = strtolower($pro['sku']);
    				//判断是否已配对
    				if(array_key_exists($sku, $aliasList)){
    					foreach ($aliasList[$sku] as $alias){
    						if( ($alias['platform'] == $pro['platform'] && $alias['selleruserid'] == $pro['selleruserid']) ||
    								($alias['platform'] == $pro['platform'] && empty($alias['selleruserid'])) ||
    								(empty($alias['platform']) && empty($alias['selleruserid']))){
    							$pro['root_sku'] = $alias['sku'];
    							$pro['matching_status'] = 3;
    							break;
    						}
    					}
    				}
    				if(empty($pro['matching_status']) && array_key_exists($sku, $exist_product_sku)){
    					$pro['root_sku'] = $exist_product_sku[$sku];
    					$pro['matching_status'] = 3;
    				}
    				//判断是否待确定
    				if(empty($pro['matching_status']) && !empty($pro['matching_info'])){
    					$matching_info = json_decode($pro['matching_info'], true);
    					if(!empty($matching_info['matching_pending'][$pro['sku']])){
    						$pro['matching_pending'] = $matching_info['matching_pending'][$pro['sku']];
    						$pro['matching_status'] = 2;
    					}
    				}
    				//未识别
    				if(empty($pro['matching_status'])){
    					$pro['matching_status'] = 1;
    				}
    			}
    			else{
    				$pro['matching_status'] = 1;
    			}
    			/*
    			//普通、非变参商品
    			if(empty($pro['rowspan'])){
    				$father_index = -1;
    				$father_pro_id = 0;
    			}
    			//变参父商品
    			else if($pro['rowspan'] != -1){
    				$father_index = $item_id;
    				$father_pro_id = $pro['pro_id'];
    				$pro['rowspan'] = 1;
    			}
    			//变参子产品
    			else if( $pro['rowspan'] == -1){
    				if($father_index == -1 || $father_pro_id != $pro['pro_id']){
    					$father_index = $item_id;
    					$father_pro_id = $pro['pro_id'];
    					$pro['rowspan'] = 1;
    				}
    				else if(!empty($data[$father_index])){
    					$data[$father_index]['rowspan'] = $data[$father_index]['rowspan'] + 1;
    					$pro['fat_id'] = $data[$father_index]['pro_id'];
    				}
    			}*/
    		}
    		
    		$data = $pro_list;
    	}
    	else{
	    	//按照键值重新排序
	    	ksort($pro_list);
	    	
	    	$sku_str = implode('@,@', $skus);
	    	//查询已存在商品库的sku，用于一键生成商品
	    	$exist_product_sku = array();
		   	if($is_create_info){
	    	    $prod = Product::find()->select(['sku'])->where("instr('".$sku_str."', sku)>0")->asArray()->all();
	    	    foreach ($prod as $p){
	    	        $exist_product_sku[] = strtolower($p['sku']);
		   	    }
		   	}
		    
	    	//查询所有的配对关系
	    	$aliasList = array();
	    	$aliasInfo = ProductAliases::find()->where(['platform' => $platform])->andwhere("instr('".$sku_str."', alias_sku)>0")->asArray()->all();
	    	//$aliasInfo = ProductAliases::find()->where(['alias_sku' => $skus])->andWhere(['platform' => $platform])->asArray()->all();
	    	foreach($aliasInfo as $alias){
	    		$aliasList[$alias['alias_sku']][] = [
		    		'sku' => $alias['sku'],
		    		'platform' => $alias['platform'],
		    		'selleruserid' => $alias['selleruserid'],
	    		];
	    	}
	    	
	    	//处理配对关系，并排除不符合的项
	    	$data = array();
	    	$count = 0;
	    	$p_s = $per_page * $page  + 1;
	    	$p_e = $per_page * ($page + 1);
	    	$father_index = -1;      //父商品位置
	    	$father_pro_id = 0;      //父商品id
	    	$item_id = 0;
	    	//print_r($pro_list);die;
	    	foreach ($pro_list as $pro){
	    		//整理配对信息
	   			if(!empty($pro['sku'])){
	   				if(array_key_exists($pro['sku'], $aliasList)){
	   					foreach ($aliasList[$pro['sku']] as $alias){
	   						if($alias['platform'] == $pro['platform'] && $alias['selleruserid'] == $pro['selleruserid']){
	   							$pro['root_sku'] = $alias['sku'];
	   							$pro['matching_status'] = 3;
	   							break;
	   						}
	   					}
	  				}
	  				
	   				if(empty($pro['root_sku']) && !empty($pro['matching_info'])){
	   					$matching_info = json_decode($pro['matching_info'], true);
	   					if(!empty($matching_info['matching_pending'][$pro['sku']])){
	   						$pro['matching_pending'] = $matching_info['matching_pending'][$pro['sku']];
	  						$pro['matching_status'] = 2;
	   					}
	   				}
	   			}
	   			
	   			//配对状态筛选
	   			if(!empty($params['matching_type'])){
	   				if($params['matching_type'] != $pro['matching_status']){
	   					continue;
	   				}
	   			}
	   			if($is_create_info && $pro['matching_status'] == 3){
	   				continue;
	   			}
	   			//SKU筛选
	   			if(!empty($params['matching_searchval']) && !empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
	   				if(empty($pro['sku'])){
	   					continue;
	   				}
		   			//变参商品
		   			if($platform == 'aliexpress' || !empty($pro['rowspan'])){
		   				if(strpos($pro['ParentSKU'], trim($params['matching_searchval'])) === false && strpos($pro['sku'], trim($params['matching_searchval'])) === false)
		   					continue;
		   			}
	   			}
	   			//一键生成商品，排除已存在商品库的
	   			if($is_create_info && in_array(strtolower($pro['sku']), $exist_product_sku)){
	   			    continue;
	   			}
	   			
	   			//普通、非变参商品
	   			if(empty($pro['rowspan'])){
	   				$count++;
	   				$father_index = -1;
	   				$father_pro_id = 0;
	   			}
	   			//变参父商品
	   			else if($pro['rowspan'] != -1){
	   				$count++;
	   				$father_index = $item_id;
	   				$father_pro_id = $pro['pro_id'];
	   				$pro['rowspan'] = 1;
	   			}
	   			//变参子产品
	   			else if( $pro['rowspan'] == -1){
	   				if($father_index == -1 || $father_pro_id != $pro['pro_id']){
	   					$count++;
		   				$father_index = $item_id;
		   				$father_pro_id = $pro['pro_id'];
		   				$pro['rowspan'] = 1;
	   				}
	   				else if(!empty($data[$father_index])){
	   					//一键创建商品时，子产品也算入总数
	   					if($is_create_info){
	   						$count++;
	   					}
	   					$data[$father_index]['rowspan'] = $data[$father_index]['rowspan'] + 1;
	   					$pro['fat_id'] = $data[$father_index]['pro_id'];
	   				}
	   			}
	   			
	   			//分页实现，只读取哪部分数据
	   			if($per_page == -1){
	   				$data[$item_id] = $pro;
	   			}
	   			else if($count >= $p_s && $count < $p_e + 1){
	   				$data[$item_id] = $pro;
	   			}
	   			$item_id++;
	    	}
	    	unset($pro_list);
	   	
	    	$pagination = new Pagination([
	   			'page' => $page,
	   			'pageSize' => $per_page,
	   			'totalCount' => $count,
	   			'pageSizeLimit' => [20,200],
	 		]);
    	}
    	
    	$result['pagination'] = $pagination;
    	$result['data'] = $data;
    	return $result;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询ebay商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/04		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_ebay($params, &$skus, $selleruser_info){
    	$selleruserid = [];
    	foreach ($selleruser_info as $id => $name){
    		$selleruserid[] = $id;
    	}
    	
    	$query= EbayItem::find()
    		->select("ebay_item.*,
        			detail.variation, detail.matching_info, detail.imgurl")
    		->innerJoin("ebay_item_detail detail", "ebay_item.itemid=detail.itemid")
    		->andWhere(['ebay_item.selleruserid'=>$selleruserid]);  //不显示 解绑的账号的订单
    	 
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'product_status':
    				if($value == 1)
    					$query->andWhere(['ebay_item.listingstatus' => 'Active']);
    				else if($value == 2)
    					$query->andWhere('ebay_item.listingstatus is null or ebay_item.listingstatus != "Active"');
    				break;
    			case 'matching_searchval':
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("ebay_item.itemtitle like '%".trim($value)."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("ebay_item.sku is null or ebay_item.sku='' or ebay_item.sku like '%".trim($value)."%'");
    				}
    				else{
    					$query->andWhere("ebay_item.itemid like '%".trim($value)."%'");
    				}
    				break;
    			case 'selleruserid':
    				$query->andWhere(['ebay_item.selleruserid'=>$value]);
    				break;
    			default:
    				break;
    		}
    	}
    	$query->orderBy("ebay_item.itemid");
    	 
    	$items = $query
    	->asArray()
    	->all();
    	 
    	$pro_list = array();
    	$skus = array();
    	foreach ($items as $item){
    		$one = [
	    		'pro_id' => $item['itemid'],
	    		'selleruserid' => $item['selleruserid'],
	    		'shopname' => $item['selleruserid'],
	    		'product_name' => $item['itemtitle'],
	    		'product_name_url' => $item['viewitemurl'],
	    		'platform' => 'ebay',
	    		'root_sku' => '',
	    		'matching_pending' => '',
	    		'matching_status' => 1,
	    		'photo_primary_url' => $item['mainimg'],
	    		'photo_others' => '',
	    		'matching_info' => $item['matching_info'],
    		];
    		
    		//整理其它图片
    		if(!empty($item['imgurl'])){
    			$photo_others = '';
	    		$url_arr = unserialize($item['imgurl']);
	    		if(!empty($url_arr)){
		    		if(is_array($url_arr)){
		    			foreach($url_arr as $url){
		    				if($url != $one['photo_primary_url']){
		    					$photo_others .= $url.'@,@';
		    				}
		    			}
		    			$one['photo_others'] = rtrim($photo_others, '@,@');
		    		}
	    		}
    		}
    		
    		//多属性
    		if($item['isvariation'] == '1'){
    			//反序列化
    			$variations = unserialize($item['variation']);
    			 
    			//整理图片url
    			$pic_url = array();
    			if(!empty($variations['Pictures'])){
    				$pic = $variations['Pictures'];
    				if(empty($pic[0])){
    					$pic = [
    					'0' => $pic,
    					];
    				}
    	
    				if(!empty($pic) && !is_array($pic)){
	    				foreach($pic as $val){
	    					foreach($val['VariationSpecificPictureSet'] as $pic_set){
	    						foreach($pic_set['PictureURL'] as $url){
	    							$pic_url[$val['VariationSpecificName']][$pic_set['VariationSpecificValue']][] = $url;
	    						}
	    					}
	    				}
    				}
    			}
    			 
    			//拆分多属性商品
    			if(!empty($variations['Variation'])){
    				$variation = $variations['Variation'];
    				if(empty($variation[0])){
    					$variation = [
    					'0' => $variation,
    					];
    				}
    				foreach($variation as $key => $chil){
    					$detail = [
	    					'rowspan' => 0,
	    					'sku' => empty($chil['SKU']) ? '' : $chil['SKU'],
	    					'attributes_arr' => [],
	    					'chil_photo_primary_url' => '',
	    					'ParentSKU' => empty($item['sku']) ? $item['itemid'] : $item['sku'],
    					];
    						
    					if($key == 0){
    						$detail['rowspan'] = count($variation);
    					}
    					else{
    						$detail['rowspan'] = -1;
    					}
    						
    					try{
    						//整理属性
    						if(!empty($chil['VariationSpecifics']['NameValueList'])){
    							$NameValueList = $chil['VariationSpecifics']['NameValueList'];
    							if(empty($NameValueList[0])){
    								$NameValueList = [
    								'0' => $NameValueList,
    								];
    							}
    							foreach($NameValueList as $list){
    								if(!is_array($list['Value'])){
    									$list['Value'] = [
    									    '0' => $list['Value'],
    									];
    								}
    								foreach ($list['Value'] as $val){
    									$detail['attributes_arr'][] = $list['Name'].': '.$val;
    										
    									//根据属性查询对应
    									if(array_key_exists($list['Name'], $pic_url)){
    										if(!empty($pic_url[$list['Name']][$val])){
    											foreach($pic_url[$list['Name']][$val] as $url){
    												$detail['chil_photo_primary_url'] = $url;
    												break;
    											}
    										}
    									}
    								}
    							}
    						}
    					}
    					catch(\Exception $e){
    						//print_r($chil);
    						//print_r($e);
    						//die;
    					}
    						
    					if(!empty($detail['sku'])){
    						$skus[] = $detail['sku'];
    					}
    					$pro_list[] = array_merge($one, $detail);
    				}
    			}
    		}
    		else{
    			$detail = [
	    			'rowspan' => 0,
	    			'sku' => empty($item['sku']) ? $item['itemid'] : $item['sku'],
	    			'attributes_arr' => [],
	    			'chil_photo_primary_url' => '',
	    			'ParentSKU' => '',
    			];
    			 
    			if(!empty($detail['sku'])){
    				$skus[] = $detail['sku'];
    			}
    			$pro_list[] = array_merge($one, $detail);
    		}
    	}
    	
    	return $pro_list;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询aliexpress商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/15		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_aliexpress($params, &$skus, $selleruser_info){
    	$selleruserid = [];
    	foreach ($selleruser_info as $id => $name){
    		$selleruserid[] = $id;
    	}
    	 
    	$query= AliexpressListing::find()
    		->select("aliexpress_listing.*,
        		detail.categoryid, detail.sku_code, detail.matching_info, detail.aeopAeProductSKUs")
        	->innerJoin("aliexpress_listing_detail detail", "aliexpress_listing.productid=detail.productid")
    		->andWhere(['aliexpress_listing.selleruserid'=>$selleruserid]);  //不显示 解绑的账号的订单
    
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'product_status':
    				if(!empty($value))
    					$query->andWhere(['aliexpress_listing.product_status' => $value]);
    				break;
    			case 'matching_searchval':
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("aliexpress_listing.subject like '%".trim($value)."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("detail.sku_code = '' or detail.sku_code like '%".trim($value)."%'");
    				}
    				else{
    					$query->andWhere("aliexpress_listing.productid like '%".trim($value)."%'");
    				}
    				break;
   				case 'selleruserid':
   					$query->andWhere(['aliexpress_listing.selleruserid'=>$value]);
   					break;
    			default:
    				break;
    		}
    	}
    	$query->orderBy("aliexpress_listing.productid");
    
    	//echo $query->createCommand()->getRawSql();
    	
    	$items = $query
    	->asArray()
    	->all();
    	
    	$product_name_url_s = "http://www.aliexpress.com/item/info/";  //产品链接前缀
    	$pro_list = array();
    	$skus = array();
    	$category_list = array();
    	foreach ($items as $item){
    		$one = [
	    		'pro_id' => $item['productid'],
	    		'selleruserid' => $item['selleruserid'],
	    		'shopname' => empty($selleruser_info[$item['selleruserid']]) ? $item['selleruserid'] : $selleruser_info[$item['selleruserid']],
	    		'product_name' => $item['subject'],
	    		'product_name_url' => $product_name_url_s.$item['productid'].'.html',
	    		'platform' => 'aliexpress',
	    		'root_sku' => '',
	    		'matching_pending' => '',
	    		'matching_status' => 1,
	    		'photo_primary_url' => $item['photo_primary'],
	    		'photo_others' => empty($item['imageurls']) ? '' : str_replace(';', '@,@', $item['imageurls']),
	    		'matching_info' => $item['matching_info'],
    		];

    		$category_arr = array();
    		if(!empty($category_list[$item['categoryid']])){
    			$category_arr = $category_list[$item['categoryid']];
    		}
    		else if(!empty($item['categoryid'])){
	    		//获取类目属性信息
	    		$aliexpressCategory = AliexpressCategory::find()->select(['attribute'])->where(['cateid'=>$item['categoryid']])->asArray()->one();
	    		if(!empty($aliexpressCategory['attribute'])){
	    			$attribute = json_decode($aliexpressCategory['attribute'], true);
	    			if(!empty($attribute)){ 
		    			foreach($attribute as $val){
		    				if(!empty($val['values'])){
			    				$category_arr[$val['id']] = [
			    					'name' => empty($val['name_en']) ? 'type' : $val['name_en'],
			    					'values' => $val['values'],
			    				];
		    				}
		    			}
	    			}
	    		}
	    		if(!empty($category_arr)){
	    			$category_list[$item['categoryid']] = $category_arr;
	    		}
    		}
    		
    		
    		//整理产品明细信息
    		$attributes_arr = array();
    		$aeopAeProductSKUs = json_decode($item['aeopAeProductSKUs'], true);
//     		print_r($aeopAeProductSKUs);
    		
    		if(!empty($aeopAeProductSKUs)){
			    foreach ($aeopAeProductSKUs as $key => $val){
			    	$detail = [
				    	'rowspan' => 0,
				    	'sku' => '',
				    	'attributes_arr' => [],
				    	'chil_photo_primary_url' => '',
				    	'ParentSKU' => $item['productid'],
				    	'other_attributes' => '',
			    	];
			    	
			    	//整理属性
			    	$attr_val = array();
			    	if(!empty($val['aeopSKUProperty'])){
			    		foreach($val['aeopSKUProperty'] as $list){
			    			$attributes_val = '';
		    				//当存在自定义属性值，则直接用
		    				/*if(!empty($list['propertyValueDefinitionName'])){
		    					$attributes_val = $list['propertyValueDefinitionName'];
		    				}*/
		    				//查询属性值id对应的属性
		    				if(!empty($category_arr[$list['skuPropertyId']])){
			    				$values = $category_arr[$list['skuPropertyId']]['values'];
			    				foreach ($values as $v){
			    					if($v['id'] == $list['propertyValueId']){
			    						$attributes_val = $v['name_en'];
			    						break;
			    					}
			    				}
		    				}
			    				
		    				if(!empty($attributes_val)){
		    					$attr_val[] = $attributes_val;
		    					$detail['attributes_arr'][] = $category_arr[$list['skuPropertyId']]['name'].': '.$attributes_val;
		    				}
		    				if(!empty($list['skuImage'])){
		    					$detail['chil_photo_primary_url'] = $list['skuImage'];
		    				}
			    		}
			    	}
			    	
			    	//整理SKU
			    	if(empty($val['skuCode'])){
			    		$detail['sku'] = $item['productid'];
			    		//添加属性值
			    		if(!empty($attr_val)){
			    			foreach($attr_val as $attr){
			    				$detail['sku'] .= $attr.' ';
			    			}
			    		}
			    		$detail['sku'] = trim($detail['sku']);
			    	}
			    	else{
			    		$detail['sku'] = $val['skuCode'];
			    	}
			    	 
			    	if(count($aeopAeProductSKUs) > 1){
			    		if($key == 0)
			    			$detail['rowspan'] = count($aeopAeProductSKUs);
			    		else
			    			$detail['rowspan'] = -1;
			    	}
			    	else{
			    		foreach($detail['attributes_arr'] as $attr){
			    			$detail['other_attributes'] .= $attr.';';
			    		}
			    		$detail['other_attributes'] = rtrim($detail['other_attributes'], ';');
			    	}
			    	 
			    	if(!empty($detail['sku'])){
			    		$skus[] = $detail['sku'];
			    	}
			    	$pro_list[] = array_merge($one, $detail);
			    }
    		}
    		
    		//整理子产品
    		$sku_code = array();
    		if(!empty($item['sku_code'])){
    			$sku_code = explode(';', $item['sku_code']);
    		}
    		
    		foreach($sku_code as $key => $sku){
    			
    		}
    	}
    	
    	return $pro_list;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询wish商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/16		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_wish($params, &$skus, $selleruser_info){
    	//查询wish的绑定账号信息
    	$site_id = '0';
    	$site_id_list = [];
    	$store = [];
    	$wish_account_list = WishHelper::getWishAccountList();
    	foreach ($wish_account_list as $acc){
    		if(in_array($acc['store_name'], $selleruser_info)){
    			$site_id_list[] = $acc['site_id'];
    			$store[$acc['site_id']] = $acc['store_name'];
    		}
    		
    		if(!empty($params['selleruserid'])){
    			if($acc['store_name'] == $params['selleruserid']){
    				$site_id = $acc['site_id'];
    			}
    		}
    	}
    	
    	$query= WishFanben::find()
	    	->select("wish_fanben.*,
	        			detail.fanben_id, detail.variance_product_id, detail.sku, detail.color, detail.size, detail.enable, detail.image_url")
	    	->leftJoin("wish_fanben_variance detail", "wish_fanben.id=detail.fanben_id")
	    	->where(['wish_fanben.type' => 1])
	    	->andWhere(['wish_fanben.site_id'=>$site_id_list]);  //不显示 解绑的账号的订单
    
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'product_status':
    				if(!empty($value)){
    					if($value == '1'){
    						$query->andWhere("wish_fanben.is_enable<3");
    						$query->andWhere("detail.enable='Y'");
    					}
    					else if($value == '2'){
    						$query->andWhere("wish_fanben.is_enable>1");
    						$query->andWhere("detail.enable='N'");
    					}
    				}
    				break;
    			case 'check_status':
    				if(!empty($value)){
    					$query->andWhere(['wish_fanben.lb_status' => $value]);
    				}
    				break;
    			case 'matching_searchval':
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("wish_fanben.name like '%".trim($value)."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("wish_fanben.parent_sku is null or wish_fanben.parent_sku='' or wish_fanben.parent_sku like '%".trim($value)."%' or detail.sku like '%".trim($value)."%'");
    				}
    				else{
    					$query->andWhere("wish_fanben.wish_product_id like '%".trim($value)."%'");
    				}
    				break;
    			case 'selleruserid':
    				$query->andWhere(['wish_fanben.site_id' => $site_id]);
   					break;
    			default:
    				break;
    		}
    	}
    	$query->orderBy("wish_fanben.id");
    
    	/*$items = $query
    	->asArray()
    	->all();*/
    	$command = $query->createCommand();
    	$items = $command->queryAll();
    	//print_r($command->getSql());die;
    	
    	$pro_list = array();
    	$exist_father_sku = array();
    	$skus = array();
    	foreach ($items as $item){
    		$one = [
	    		'pro_id' =>$item['id'],
	    		'selleruserid' => empty($store[$item['site_id']]) ? '' : $store[$item['site_id']],
	    		'shopname' => empty($store[$item['site_id']]) ? '' : $store[$item['site_id']],
	    		'product_name' => $item['name'],
	    		'product_name_url' => '',
	    		'platform' => 'wish',
	    		'root_sku' => '',
	    		'matching_pending' => '',
	    		'matching_status' => 1,
	    		'photo_primary_url' => $item['main_image'],
	    		'photo_others' => '',
	    		'matching_info' => $item['matching_info'],
    		];
    
    		//整理图片信息
    		for($n = 1; $n <= 10; $n++){
    			if(!empty($item['extra_image_'.$n])){
    				$one['photo_others'] .= $item['extra_image_'.$n].'@,@';
    			}
    		}
    		$one['photo_others'] = rtrim($one['photo_others'], '@,@');
    
    		//整理子产品信息
    		if(!empty($item['fanben_id'])){
    			$detail = [
	    			'rowspan' => 0,
	    			'ParentSKU' => empty($item['parent_sku']) ? '' : $item['parent_sku'],
	    			'sku' => $item['sku'],
	    			'attributes_arr' => [],
	    			'chil_photo_primary_url' => $item['image_url'],
	    			'other_attributes' => '',
    			];
    			
    			if(empty($item['color']) && empty($item['size'])){
    				$detail['ParentSKU'] = '';
    			}
    			else{
	    			//属性
	    			if(!empty($item['color'])){
	    				$detail['attributes_arr'][] = 'color: '.$item['color'];
	    			}
	    			if(!empty($item['size'])){
	    				$detail['attributes_arr'][] = 'size: '.$item['size'];
	    			}
	    			 
	    			if(!empty($item['parent_sku'])){
	    				if(in_array($item['parent_sku'], $exist_father_sku)){
	    					$detail['rowspan'] = -1;
	    				}
	    				else{
	    					$detail['rowspan'] = $item['variance_count'];
	    					$exist_father_sku[] = $item['parent_sku'];
	    				}
	    			}
    			}
    			
    			if(!empty($detail['sku'])){
    				$skus[] = $item['sku'];
    			}
    			$pro_list[] = array_merge($one, $detail);
    		}
    		else{
    			$detail = [
	    			'rowspan' => 0,
	    			'ParentSKU' => '',
	    			'sku' => empty($item['parent_sku']) ? '' : $item['parent_sku'],
	    			'attributes_arr' => [],
	    			'chil_photo_primary_url' => '',
	    			'other_attributes' => '',
    			];
    			$pro_list[] = array_merge($one, $detail);
    		}
    	}
    	
    	return $pro_list;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询cdiscount商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/17		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_cdiscount($params, &$skus, $selleruser_info){
    	$selleruserid = [];
    	foreach ($selleruser_info as $id => $name){
    		$selleruserid[] = $id;
    	}
    	
    	//查询所有父亲商品id
    	$parent_product_id_list = array();
    	$father_list  = CdiscountOfferList::find()->select("parent_product_id")->where("parent_product_id!=''")->asArray()->all();
    	foreach ($father_list as $father){
    		$parent_product_id_list[] = $father['parent_product_id'];
    	}
    	
    	$query = CdiscountOfferList::find()
    	//排除存在子产品的父商品
    	->where(['not in','product_id', $parent_product_id_list])
    		->andWhere(['seller_id'=>$selleruserid]);  //不显示 解绑的账号的订单
    
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'product_status':
    				if(!empty($value)){
    					if($value == '1')
    						$query->andWhere("offer_state='Active'");
    					else if($value == '2')
    						$query->andWhere("offer_state!='Active'");
    				}
    				break;
    			case 'matching_searchval':
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("name like '%".trim($value)."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("seller_product_id like '%".trim($value)."%'");
    				}
    				break;
   				case 'selleruserid':
   					$query->andWhere(['seller_id'=>$value]);
   					break;
    			default:
    				break;
    		}
    	}
    	
    	$items = $query
    		->asArray()
    		->all();
    	
    	$pro_list = array();
    	$skus = array();
    	foreach ($items as $item){
    		$one = [
	    		'pro_id' =>$item['id'],
	    		'selleruserid' => $item['seller_id'],
	    		'shopname' => empty($selleruser_info[$item['seller_id']]) ? $item['seller_id'] : $selleruser_info[$item['seller_id']],
	    		'product_name' => $item['name'],
	    		'product_name_url' => $item['product_url'],
	    		'platform' => 'cdiscount',
	    		'root_sku' => '',
	    		'matching_pending' => '',
	    		'matching_status' => 1,
	    		'photo_primary_url' => '',
	    		'photo_others' => '',
	    		'matching_info' => $item['matching_info'],
	    		'rowspan' => 0,
	    		'ParentSKU' => $item['parent_product_id'],
	    		'sku' => $item['seller_product_id'],
	    		'attributes_arr' => [],
	    		'chil_photo_primary_url' => '',
	    		'other_attributes' => '',
    		];
    		
    		//整理图片信息
    		if(!empty($item['img'])){
    			$photo_arr = json_decode($item['img'], true);
    			if(!empty($photo_arr)){
    				$count = 0;
    				foreach($photo_arr as $url){
    					if($count == 0){
    						$one['photo_primary_url'] = $url;
    						$count++;
    					}
    					else 
    						$one['photo_others'] .= $url.'@,@';
    				}
    				$one['photo_others'] = rtrim($one['photo_others'], '@,@');
    			}
    		}
    		
    		if(!empty($one['sku'])){
    			$skus[] = $one['sku'];
    		}
    		
    		//整理键值，方便排序
    		for($n = 0; $n < 1000; $n += 10){
    			$key = strtolower($one['sku']).sprintf("%08d", $n);
    			if(!array_key_exists($key, $pro_list)){
    				$pro_list[$key] = $one;
    				break;
    			}
    		}
    	}
    	
    	return $pro_list;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询lazada商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/19		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_lazada($params, &$skus, $selleruser_info){
    	//查询lazada的绑定账号信息
    	$lazada_uid = [];
    	$lazada_uid_list = [];
    	$store = [];
    	$selleruserid = [];
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$lazadaUsers = SaasLazadaUser::find()->select(['lazada_uid', 'platform_userid', 'store_name'])->where(['puid'=>$puid, 'platform'=>'lazada'])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
    	foreach ($lazadaUsers as $user){
    		if(array_key_exists($user['platform_userid'], $selleruser_info)){
    			$lazada_uid_list[] = $user['lazada_uid'];
    			$store[$user['lazada_uid']] = empty($user['store_name']) ? $user['platform_userid'] : $user['store_name'];
    			$selleruserid[$user['lazada_uid']] = $user['platform_userid'];
    		}
    		
    		if(!empty($params['selleruserid'])){
    			if($user['platform_userid'] == $params['selleruserid']){
    				$lazada_uid[] = $user['lazada_uid'];
    			}
    		}
    	}
    	 
    	$query= LazadaListingV2::find()->select(['id', 'lazada_uid', 'name', 'SellerSku', 'Skus', 'group_id', 'matching_info'])
    		->andWhere(['lazada_uid'=>$lazada_uid_list]);  //不显示 解绑的账号的订单
    
    	$product_status = '';
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'check_status':
    				if(!empty($value)){
    					$query->andWhere(['sub_status' => $value]);
    				}
    				break;
   				case 'product_status':
   					if(!empty($value)){
   						if($value == '1')
   							$product_status = 'active';
   						else if($value == '2')
   							$product_status = 'inactive';
   						else if($value == '3')
   							$product_status = 'deleted';
   					}
   					break;
    			case 'matching_searchval':
    				$value = str_replace("'", "\'", trim($value));
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("name like '%".$value."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("SellerSku like '%".$value."%'");
    				}
    				break;
   				case 'selleruserid':
    				$query->andWhere(['lazada_uid'=>$lazada_uid]);
    				break;
    			default:
    				break;
    		}
    	}
    	
    	//当超过10000条，分批读取
    	$query_count = $query->count();
    	if($query_count > 10000){
    		$page = 0;
    		$pageSize = 10000;
    		$items = array();
    		while($query_count > 0){
    			$pagination = new Pagination([
    				'page' => $page,
    				'pageSize' => $pageSize,
    				'pageSizeLimit' => [5,$pageSize],
    			]);
    			
    			$item = $query
	    			->offset($pagination->offset)
	    			->limit($pagination->limit)
	    			->asArray()
	    			->all();
    			$items = array_merge($items, $item);
    			
    			$query_count = $query_count - $pageSize;
    			$page++;
    			
    			if($page > 2){
    				break;
    			}
    		}
    	}
    	else{
    		$items = $query
	    		->asArray()
	    		->all();
    	}
    	//print_r(count($items));die;
    	
    	$exist_product_info = array();
    	$pro_list = array();
    	$skus = array();
    	foreach ($items as $item){
    		$one = [
	    		'pro_id' =>$item['id'],
	    		'selleruserid' => empty($selleruserid[$item['lazada_uid']]) ? '' : $selleruserid[$item['lazada_uid']],
	    		'shopname' => empty($store[$item['lazada_uid']]) ? '' : $store[$item['lazada_uid']],
	    		'product_name' => $item['name'],
	    		'product_name_url' => '',
	    		'platform' => 'lazada',
	    		'root_sku' => '',
	    		'matching_pending' => '',
	    		'matching_status' => 1,
	    		'photo_primary_url' => '',
	    		'photo_others' => '',
	    		'matching_info' => $item['matching_info'],
	    		'rowspan' => 0,
	    		'ParentSKU' => '',
	    		'sku' => $item['SellerSku'],
	    		'attributes_arr' => [],
	    		'chil_photo_primary_url' => '',
	    		'other_attributes' => '',
    		];
    
    		//整理其它信息
    		if(!empty($item['Skus'])){
    			$info = json_decode($item['Skus'], true);
    			//商品状态筛选
    			if(!empty($product_status)){
    				if(empty($info['Status']) || strtolower($info['Status']) != $product_status)
    					continue;
    			}
    			//商品链接
    			if(!empty($info['url'])){
    				$one['product_name_url'] = $info['url'];
    			}
    			//产品属性
    			if(!empty($info['_compatible_variation_']) && strpos($info['_compatible_variation_'], '...') === false){
    				$one['attributes_arr'][] = 'type: '.$info['_compatible_variation_'];
    			}
    			//图片
    			if(!empty($info['Images'])){
    				$count = 0;
    				foreach ($info['Images'] as $key => $image){
    					if(!empty($image)){
	    					if($count == 0){
	    						$one['photo_primary_url'] = $image;
	    						$count++;
	    					}
	    					else{
	    						$one['photo_others'] .= $image.'@,@';
	    					}
    					}
    				}
    				$one['photo_others'] = rtrim($one['photo_others'], '@,@');
    			}
    		}
    		
    		//判断，当同一账号，不同站点时，只取一个
    		$pro_key = !empty($item['group_id']) ? strtolower($item['group_id']) : strtolower($one['sku']);
    		$pro_key .= '@@@'.$one['selleruserid'].'@@@'.$one['sku'];
    		if(in_array($pro_key, $exist_product_info)){
    			continue;
    		}
    		else{
    			$exist_product_info[] = $pro_key;
    		}
    
    		if(!empty($one['sku'])){
    			$skus[] = $one['sku'];
    		}
    		
    		$group_id = !empty($item['group_id']) ? strtolower($item['group_id']) : strtolower($one['sku']);
    		//更新父商品信息
    		$father_key = $group_id.$one['selleruserid'].sprintf("%08d", 0);
    		if(array_key_exists($father_key, $pro_list)){
    			$pro_list[$father_key]['rowspan']++;
    			$one['rowspan'] = -1;
    		}
    		//整理键值，方便排序
    		for($n = 0; $n < 1000; $n += 10){
    			$key = $group_id.$one['selleruserid'].sprintf("%08d", $n);
    			if(!array_key_exists($key, $pro_list)){
    				$pro_list[$key] = $one;
    				break;
    			}
    		}
    	}
    	//print_r($pro_list);die;
    	return $pro_list;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询linio商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/20		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_linio($params, &$skus, $selleruser_info){
    	//查询lazada的绑定账号信息
    	$lazada_uid = [];
    	$lazada_uid_list = [];
    	$store = [];
    	$selleruserid = [];
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$lazadaUsers = SaasLazadaUser::find()->select(['lazada_uid', 'platform_userid', 'store_name'])->where(['puid'=>$puid, 'platform'=>'linio'])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
    	foreach ($lazadaUsers as $user){
    		if(array_key_exists($user['platform_userid'], $selleruser_info)){
    			$lazada_uid_list[] = $user['lazada_uid'];
    			$store[$user['lazada_uid']] = empty($user['store_name']) ? $user['platform_userid'] : $user['store_name'];
    			$selleruserid[$user['lazada_uid']] = $user['platform_userid'];
    		}
    		
    		if(!empty($params['selleruserid'])){
    			if($user['platform_userid'] == $params['selleruserid']){
    				$lazada_uid[] = $user['lazada_uid'];
    			}
    		}
    	}
    
    	$query= LazadaListing::find()
    		->andWhere(['lazada_uid_id'=>$lazada_uid_list])  //不显示 解绑的账号的订单
    		->andWhere(['platform' => 'linio']);
    
    	$product_status = '';
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'product_status':
    				if(!empty($value)){
    					if($value == '1')
    					    $query->andWhere(['Status' => 'active']);
    					else if($value == '2')
    					    $query->andWhere(['Status' => 'inactive']);
    					else if($value == '3')
    					    $query->andWhere(['Status' => 'deleted']);
    				}
    				break;
    			case 'matching_searchval':
    				$value = str_replace("'", "\'", trim($value));
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("name like '%".$value."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("SellerSku like '%".$value."%'");
    				}
    				break;
   				case 'selleruserid':
   					$query->andWhere(['lazada_uid_id'=>$lazada_uid]);
   					break;
    			default:
    				break;
    		}
    	}
    
    	$items = $query
    	->asArray()
    	->all();
    	 
    	$exist_product_info = array();
    	$pro_list = array();
    	$skus = array();
    	foreach ($items as $item){
    		$one = [
        		'pro_id' =>$item['id'],
        		'selleruserid' => empty($selleruserid[$item['lazada_uid_id']]) ? '' : $selleruserid[$item['lazada_uid_id']],
        		'shopname' => empty($store[$item['lazada_uid_id']]) ? '' : $store[$item['lazada_uid_id']],
        		'product_name' => $item['Name'],
        		'product_name_url' => $item['Url'],
        		'platform' => 'linio',
        		'root_sku' => '',
        		'matching_pending' => '',
        		'matching_status' => 1,
        		'photo_primary_url' => $item['MainImage'],
        		'photo_others' => '',
        		'matching_info' => $item['matching_info'],
        		'rowspan' => 0,
        		'ParentSKU' => '',
        		'sku' => $item['SellerSku'],
        		'attributes_arr' => [],
        		'chil_photo_primary_url' => '',
        		'other_attributes' => '',
    		];
    
    		//判断，当同一账号，不同站点时，只取一个
    		$pro_key = !empty($item['ParentSku']) ? strtolower($item['ParentSku']) : strtolower($one['sku']);
    		$pro_key .= '@@@'.$one['selleruserid'].'@@@'.$one['sku'];
    		if(in_array($pro_key, $exist_product_info)){
    			continue;
    		}
    		else{
    			$exist_product_info[] = $pro_key;
    		}
    		
    		//产品属性
    		if(!empty($item['Variation']) && strpos($item['Variation'], '...') === false){
    			$one['attributes_arr'][] = 'type: '.$item['Variation'];
    		}
    
    		if(!empty($one['sku'])){
    			$skus[] = $one['sku'];
    		}
    
    		$group_id = !empty($item['ParentSku']) ? strtolower($item['ParentSku']) : strtolower($one['sku']);
    		//更新父商品信息
    		$father_key = $group_id.$one['selleruserid'].sprintf("%08d", 0);
    		if(array_key_exists($father_key, $pro_list)){
    			$pro_list[$father_key]['rowspan']++;
    			$one['rowspan'] = -1;
    		}
    		//整理键值，方便排序
    		for($n = 0; $n < 1000; $n += 10){
    			$key = $group_id.$one['selleruserid'].sprintf("%08d", $n);
    			if(!array_key_exists($key, $pro_list)){
    				$pro_list[$key] = $one;
    				break;
    			}
    		}
    	}
    	//print_r($pro_list);die;
    	return $pro_list;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	 查询jumia商品信息
     +---------------------------------------------------------------------------------------------
     * @param  $params              array()               条件
     * @param  $skus                array()               sku信息，引用传递
     * @param  $selleruser_info     array(id => name)     店铺信息
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/20		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getProductInfo_jumia($params, &$skus, $selleruser_info, $is_create_info){
    	//查询lazada的绑定账号信息
    	$lazada_uid = [];
    	$lazada_uid_list = [];
    	$store = [];
    	$selleruserid = [];
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$lazadaUsers = SaasLazadaUser::find()->select(['lazada_uid', 'platform_userid', 'store_name'])->where(['puid'=>$puid, 'platform'=>'jumia'])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
    	foreach ($lazadaUsers as $user){
    		if(array_key_exists($user['platform_userid'], $selleruser_info)){
    			$lazada_uid_list[] = $user['lazada_uid'];
    			$store[$user['lazada_uid']] = empty($user['store_name']) ? $user['platform_userid'] : $user['store_name'];
    			$selleruserid[$user['lazada_uid']] = $user['platform_userid'];
    		}
    
    		if(!empty($params['selleruserid'])){
    			if($user['platform_userid'] == $params['selleruserid']){
    				$lazada_uid[] = $user['lazada_uid'];
    			}
    		}
    	}
    
    	$query= LazadaListing::find()
    		->select(['lazada_uid_id', 'id', 'Name', 'Url', 'MainImage', 'matching_info', 'ParentSku', 'SellerSku'])
    		->andWhere(['lazada_uid_id' => $lazada_uid_list])  //不显示 解绑的账号的订单
    		->andWhere(['platform' => 'jumia']);
    	
    	//生成商品
    	if($is_create_info || !empty($params['is_not_matching'])){
    		//非已配对
    		$query->andWhere("SellerSku not in (select sku from pd_product) and SellerSku not in (select alias_sku from pd_product_aliases)");
    	}
    
    	$product_status = '';
    	foreach ($params as $key=>$value){
    		if($value=='')
    			continue;
    		switch ($key){
    			case 'product_status':
    				if(!empty($value)){
    					if($value == '1')
    						$query->andWhere(['Status' => 'active']);
    					else if($value == '2')
    						$query->andWhere(['Status' => 'inactive']);
    					else if($value == '3')
    						$query->andWhere(['Status' => 'deleted']);
    				}
    				break;
    			case 'matching_searchval':
    				$value = str_replace("'", "\'", trim($value));
    				if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'title'){
    					$query->andWhere("name like '%".$value."%'");
    				}
    				else if(!empty($params['matching_searchval_type']) && $params['matching_searchval_type'] == 'sku'){
    					$query->andWhere("SellerSku like '%".$value."%' or ParentSku like '%".(ltrim($value, 'T-'))."%'");
    				}
    				break;
    			case 'selleruserid':
    				$query->andWhere(['lazada_uid_id'=>$lazada_uid]);
    				break;
    			case 'matching_type':
    				if(!empty($value)){
    					if($value == '1')
    						//未识别
    						$query->andWhere("SellerSku not in (select sku from pd_product) and SellerSku not in (select alias_sku from pd_product_aliases) and (matching_info is null || matching_info not like '%matching_pending%')");
    					else if($value == '2'){
    						//待确认
    						$query->andWhere("SellerSku not in (select sku from pd_product) and SellerSku not in (select alias_sku from pd_product_aliases) and matching_info like '%matching_pending%'");
    					}
    					else if($value == '3')
    						//已配对
    						$query->andWhere("(SellerSku in (select sku from pd_product) or SellerSku in (select alias_sku from pd_product_aliases))");
    				}
    				break;
    			default:
    				break;
    		}
    	}
    	
    	if($params['per-page'] == -1){
    		$pagination = new Pagination([
    			'page' => 0,
    			'pageSize' => 5000,
    			'totalCount' => $query->count(),
    			'pageSizeLimit' => [20, 200],
    		]);
    	}
    	else{
    		//合并相同的SKU
    		$query->distinct('ParentSku, SellerSku');
    		
	    	$pagination = new Pagination([
	    		'page' => $params['page'],
	    		'pageSize' => $params['per-page'],
	    		'totalCount' => $query->count(),
	    		'pageSizeLimit' => [20, 200],
	    	]);
    	}
    	 
    	$items = $query
    		->distinct()
    		->orderBy("lazada_uid_id, ParentSku, SellerSku")
	    	->limit($pagination->limit)
	    	->offset($pagination->offset)
	    	->asArray()
	    	->all();
    	
    	$exist_product_info = array();
    	$pro_list = array();
    	$skus = array();
    	foreach ($items as $item){
    		$one = [
	    		'pro_id' =>$item['id'],
	    		'selleruserid' => empty($selleruserid[$item['lazada_uid_id']]) ? '' : $selleruserid[$item['lazada_uid_id']],
	    		'shopname' => empty($store[$item['lazada_uid_id']]) ? '' : $store[$item['lazada_uid_id']],
	    		'product_name' => $item['Name'],
	    		'product_name_url' => $item['Url'],
	    		'platform' => 'jumia',
	    		'root_sku' => '',
	    		'matching_pending' => '',
	    		'matching_status' => 0,
	    		'photo_primary_url' => $item['MainImage'],
	    		'photo_others' => '',
	    		'matching_info' => $item['matching_info'],
	    		'rowspan' => 0,
	    		'sku' => $item['SellerSku'],
	    		'ParentSKU' => $item['ParentSku'],
	    		'attributes_arr' => [],
	    		'chil_photo_primary_url' => '',
	    		'other_attributes' => '',
    		];
    
    		//产品属性
    		if(!empty($item['Variation']) && strpos($item['Variation'], '...') === false){
    			$one['attributes_arr'][] = 'type: '.$item['Variation'];
    		}
    
    		if(!empty($one['sku'])){
    			$skus[] = $one['sku'];
    		}
    
    		$group_id = !empty($one['ParentSKU']) ? strtolower($one['ParentSKU']) : strtolower($one['sku']);
    		//更新父商品信息
    		$father_key = $group_id.$one['selleruserid'].sprintf("%08d", 0);
    		if(array_key_exists($father_key, $pro_list)){
    			if(empty($pro_list[$father_key]['rowspan'])){
    				$pro_list[$father_key]['rowspan'] = 1;
    				$pro_list[$father_key]['ParentSKU'] = 'T-'.$pro_list[$father_key]['ParentSKU'];
    			}
    			$pro_list[$father_key]['rowspan']++;
    			$one['rowspan'] = -1;
    			$one['fat_id'] = $pro_list[$father_key]['pro_id'];
    		}
    		else if(strtolower($one['ParentSKU']) != strtolower($one['sku'])){
    			$one['rowspan'] = 1;
    			$one['ParentSKU'] = 'T-'.$one['ParentSKU'];
    		}
    		//整理键值，方便排序
    		for($n = 0; $n < 1000; $n += 10){
    			$key = $group_id.$one['selleruserid'].sprintf("%08d", $n);
    			if(!array_key_exists($key, $pro_list)){
    				$pro_list[$key] = $one;
    				break;
    			}
    		}
    	}
    	//print_r($pro_list);die;
    	
    	return [$pro_list, $pagination];
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     *	变更在线商品的配对关系
     +---------------------------------------------------------------------------------------------
     * @param  $platform   string     平台
     * @param  $pro_id     string     在线商品产品ID
     * @param  $sku        string     在线商品SKU
     * @param  $root_sku   string     本地商品SKU
     * @param  $matching_type   int   1 配对， 2 解绑
     +---------------------------------------------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2017/05/09		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function ChangeMatchingProduct($platform, $pro_id, $sku, $root_sku, $matching_type){
    	$ret['success'] = true;
    	$ret['msg'] = '';
    	
    	//查询selleruserid
    	$pro = array();
    	$selleruserid = '';
    	if($platform == 'aliexpress'){
    		$pro = AliexpressListing::findOne(['productid' => $pro_id]);
    		$selleruserid = $pro->selleruserid;
    	}
    	else if($platform == 'wish'){
    		$pro = WishFanben::findOne(['id' => $pro_id]);
    		if(!empty($pro['site_id'])){
    		    $wish = SaasWishUser::find()->select(['store_name'])->where(['site_id' => $pro['site_id']])->asArray()->one();
    		    if(!empty($wish['store_name'])){
    		        $selleruserid = $wish['store_name'];
    		    }
    		}
    	}
    	else if($platform == 'cdiscount'){
    		$pro = CdiscountOfferList::findOne(['id' => $pro_id]);
    		$selleruserid = $pro->selleruserid;
    	}
    	else if($platform == 'lazada'){
    		$pro = LazadaListingV2::find()->select(['lazada_uid'])->where(['id' => $pro_id])->asArray()->one();
    		if(!empty($pro['lazada_uid'])){
    			$lazada = SaasLazadaUser::find()->select(['platform_userid'])->where(['lazada_uid' => $pro['lazada_uid']])->asArray()->one();
    			if(!empty($lazada['platform_userid'])){
    				$pro['selleruserid'] = $lazada['platform_userid'];
    			}
    		}
    	}
    	else if(in_array($platform, ['linio', 'jumia'])){
    		$pro = LazadaListing::find()->select(['lazada_uid_id'])->where(['id' => $pro_id])->asArray()->one();
    		if(!empty($pro['lazada_uid_id'])){
    			$lazada = SaasLazadaUser::find()->select(['platform_userid'])->where(['lazada_uid' => $pro['lazada_uid_id']])->asArray()->one();
    			if(!empty($lazada['platform_userid'])){
    				$pro['selleruserid'] = $lazada['platform_userid'];
    			}
    		}
    	}
    	else{
    		$pro = EbayItem::find()->select(['selleruserid'])->where(['itemid' => $pro_id])->asArray()->one();
    	}
    	
    	if(!empty($pro) && !empty($pro['selleruserid'])){
    		$selleruserid = $pro['selleruserid'];
    		
    		if($matching_type == 2){
    			//解绑配对
    			$alias = ProductAliases::findOne(['sku' => $root_sku, 'alias_sku' => $sku, 'platform' => $platform, 'selleruserid' => $selleruserid]);
    			if(!empty($alias)){
    				$alias->delete();
    			}
    		}
    		else if($matching_type == 1){
    			//配对、更改配对
    			$alias = ProductAliases::findOne(['alias_sku' => $sku, 'platform' => $platform, 'selleruserid' => $selleruserid]);
    			if(!empty($alias)){
    				$alias->sku = $root_sku;
    				
    				if(!$alias->save(false)){
    					$ret['success'] = false;
    					$ret['msg'] = "平台SKU: $sku 配对 本地SKU$root_sku 失败！";
    				}
    			}
    			else{
    				$alias = new ProductAliases();
    				$alias->sku = $root_sku;
    				$alias->alias_sku = $sku;
    				$alias->platform = $platform;
    				$alias->selleruserid = $selleruserid;
    				
    				if(!$alias->save(false)){
    					$ret['success'] = false;
    					$ret['msg'] = "平台SKU: $sku 配对 本地SKU$root_sku 失败！";
    				}
    				
    				//清除待确认信息
    				$detail = array();
    				if($platform == 'aliexpress'){
    					$detail = AliexpressListingDetail::findOne(['productid' => $pro_id]);
    				}
    				else if($platform == 'wish'){
    					$detail = WishFanben::findOne(['id' => $pro_id]);
    				}
    				else if($platform == 'cdiscount'){
    					$detail = CdiscountOfferList::findOne(['id' => $pro_id]);
    				}
    				else if($platform == 'lazada'){
    					$detail = LazadaListingV2::findOne(['id' => $pro_id]);
    				}
    				else if(in_array($platform, ['linio', 'jumia'])){
    					$detail = LazadaListing::findOne(['id' => $pro_id]);
    				}
    				else{
    					$detail = EbayItemDetail::findOne(['itemid' => $pro_id]);
    				}
    				
    				if(!empty($detail)){
    					$matching_info = array();
    					if(!empty($detail->matching_info)){
    						$matching_info = json_decode($detail->matching_info, true);
    						if(!empty($matching_info['matching_pending'][$sku])){
    							unset($matching_info['matching_pending'][$sku]);
    							$detail->matching_info = json_encode($matching_info);
    							$detail->save(false);
    						}
    					}
    				}
    			}
    		}
    	}
    	else{
    		$ret['success'] = false;
    		$ret['msg'] = '平台商品店铺信息丢失！';
    	}
    	
    	return $ret;
    }
}
