<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\report\helpers;

use yii;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\catalog\models\ProductTags;
use common\helpers\Helper_Currency;
use yii\db\Query;

class ReportHelper{
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 标签统计 返回数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/23				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagInventory($sort, $order, $defaultPageSize=5){
		$conn=\Yii::$app->subdb;
		
		// Pagination 插件自动获取url 上page和per-page（Pagination默认参数，可以修改）这两个name的参数
		// 然后获得$pagination->limit 即 $pageSizet 和 $pagination->offse 即 $page 校正过后的值。
		// 为配合分页功能，请尽量不要自己定义 “每页多少行()” 和 “第几页” 这两参数。
		// 如果硬是自己定义了,就在Pagination初始化时覆盖'pageParam'和'pageSizeParam'为你使用的参数也可。否则分页功能生成的链接会出现异常。
		
		$queryTmp = new Query;
		
		$queryTmp->select("t1.tag_name, t.tag_id, count(t.sku) count, group_concat(t.sku SEPARATOR '\t') skuList")
			->from("pd_product_tags t")
			->leftJoin("pd_tag t1", "t1.tag_id = t.tag_id")
			->groupBy("t.tag_id,t1.tag_name");
		
		$DataCount = $queryTmp->count("1", $conn);
		
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount,
				]);
		
		$data['pagination'] = $pagination;
		
		if(empty($sort)){
			$sort = ' t.tag_id ';
			$order = 'desc';
		}
			
		$queryTmp->orderBy($sort." ".$order);
		$queryTmp->limit($pagination->limit);
		$queryTmp->offset($pagination->offset);
		
		$allTagDataArr = $queryTmp->createCommand($conn)->queryAll();
		
		$index = $pagination->offset + 1;
		$data['data'] = array();
		
		foreach ($allTagDataArr as $row){
			$skuArray = explode("\t", $row['skuList']);
				
			$stock = self::getStock($skuArray);
			// it returns like : array('stock'=>$total_stock, 'stock_value'=>$total_stock_value,'sku_count'=>$sku_count, 'skus'=>$sku_having_stock );
				
			$queryTmp2 = new Query;
			$queryTmp2->select("t1.brand_id")
				->from("pd_product t")
				->leftJoin("pd_brand t1", "t.brand_id = t1.brand_id")
				->where(["in", "t.sku" ,$stock['skus']])
				->groupBy("t1.brand_id");
			
			$tmpBrand = $queryTmp2->count("1", $conn);
				
			$data['data'][] = array('id' => $index, 'tag_id' => $row['tag_id'], 'tag_name' => $row['tag_name'], 'sku' =>  $stock['sku_count'] , 'stock' => strval($stock['stock']), 'brands' => $tmpBrand, 'stock_value' => $stock['stock_value']);
			$index++;
		}
		
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据传入的 $skuArray 来返回对应的库存
	 +----------------------------------------------------------
	 * @access private
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/23				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	private static function getStock($skuArray) {
		$conn=\Yii::$app->subdb;
	
		$query = new Query;
		$query->select('sum(qty_in_stock) as stock, sum(qty_in_stock * average_price) as stock_value, sku')
		->from('wh_product_stock')
		->where(['in', 'sku', $skuArray])
		->groupBy('sku');
	
		$command = $query->createCommand($conn);
	
		$results = $command->queryAll();
	
		$total_stock = 0;
		$total_stock_value = 0;
		$sku_count = 0;
		$sku_having_stock = array();
	
		foreach ($results as $aResult){
			if ($aResult['stock']<>0) {	//库存=0不计入
				$sku_count ++;
				$total_stock += $aResult['stock'];
				$total_stock_value += $aResult['stock_value'];
				$sku_having_stock[$aResult['sku']] = $aResult['sku'];
			}
		}
	
		return array('stock'=>$total_stock, 'stock_value'=>$total_stock_value,'sku_count'=>$sku_count, 'skus'=>$sku_having_stock );
	}
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 标签统计 返回显示明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/23				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagInventoryDetail($tag_id, $sort = 'stock', $order = 'desc') {
		$conn=\Yii::$app->subdb;
	
		$SkuInparams = $conn->createCommand("select sku from pd_product_tags where tag_id=:tag_id",["tag_id"=>$tag_id])->queryAll();
	
		$skuarray = array();
		foreach ($SkuInparams as $asku) {
			$skuarray[] = $asku['sku'];
		}
	
		$results = self::getStockDetail($skuarray, $sort, $order);
	
		return $results;
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据传入的$skuarray 返回对应的sku明细
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/23				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	private static function getStockDetail($skuarray, $sort = 'stock', $order = 'desc') {
		$conn=\Yii::$app->subdb;
	
		$query = new Query;
	
		$query->select("sum(t.qty_in_stock) as stock,
				 sum(t.qty_in_stock * t.average_price) as stock_value, t.sku, t1.prod_name_ch as name,('CNY') as currency")
					 ->from('wh_product_stock t,pd_product t1')
					 ->where(['and', 't1.sku=t.sku', 't.qty_in_stock<>0', ['in', 't.sku', $skuarray]])
					 ->groupBy('t.sku');
	
		$DataCount = $query->count("1",$conn);
	
		$pagination = new Pagination([
				'defaultPageSize' => 5,
				'totalCount' => $DataCount,
				]);
	
		$data['pagination'] = $pagination;
	
		$query->orderBy($sort." ".$order);
		$query->limit($pagination->limit);
		$query->offset($pagination->offset);
	
		$data['data'] = $query->createCommand($conn)->queryAll();
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取标签列表及其使用次数
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/23				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function listTagData(){
		$conn=\Yii::$app->subdb;
	
		$result = $conn->createCommand("select t1.tag_id,t1.tag_name,count(t.sku) as count ".
				" from pd_product_tags t ".
				" right join pd_tag t1 on t1.tag_id = t.tag_id ".
				" group by t.tag_id,t1.tag_name ".
				" order by t1.tag_id desc ")->queryAll();
	
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 返回多标签组合时的组合数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/24				初始化
	 +----------------------------------------------------------
	 * @Param
	 * $ids 获取tagId数据
	 **/
	public static function getTagsInventory($ids) {
		$conn=\Yii::$app->subdb;
	
		$skuList = array();
		foreach ($ids as $id){
			$tempSkus = self::getSkuByTagID($id);
			foreach ($tempSkus as $aSKU)
				$skuList[] = $aSKU['sku'];
		}
		
		$queryTmp = new Query;
		
		$queryTmp->select("sku")
			->from('pd_product')
			->where(['in', 'sku', $skuList]);
		
		$products_has_sku = $queryTmp->createCommand($conn)->queryAll();
	
		$list = array();
		foreach($products_has_sku as $k => $v){
			$list[] = $v['sku'];
		}
	
		$list = array_unique($list);
		$row = array();
	
		if (count($list) > 0){
			$stock = self::getStock($list);
			// it returns like : array('stock'=>$total_stock, 'stock_value'=>$total_stock_value,'sku_count'=>$sku_count, 'skus'=>$sku_having_stock );
				
			$sku_having_stock = $stock['skus'];
			$row['stock'] = $stock['stock'] == null ? 0 : $stock['stock'];
				
			$queryTmp2 = new Query;
			
			$queryTmp2->select("t1.brand_id")
				->from('pd_product t')
				->join("JOIN", "pd_brand t1", "t.brand_id = t1.brand_id")
				->where(['in', 't.sku', $sku_having_stock])
				->groupBy('t1.brand_id');
			
			$row['brands'] = $queryTmp2->count("1",$conn);
			$row['sku'] =  $stock['sku_count'];
			$row['stock_value'] = $stock['stock_value'];
		}else{
			$row['stock'] = 0;
			$row['brands'] = 0;
			$row['stock_value'] = 0;
			$row['sku'] = 0;
		}
	
		return $row;
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据传入的$id 来返回pd_product_tags 中的sku
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/24				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	private static function getSkuByTagID($id) {
		$skus = ProductTags::find ()->select('sku')->where ('tag_id=:tag_id', [':tag_id' => $id] )->asArray()->all();
		return $skus;
	}
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 多标签统计 返回显示明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/24				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagsInventoryDetail($tagIDs, $sort = 'stock', $order = 'desc') {
		$conn=\Yii::$app->subdb;
	
		$tagIDs = "'".$tagIDs."'";
		$tagIdList = str_replace(",","','",$tagIDs);
	
		$SkuInparams = $conn->createCommand("select sku from pd_product_tags ".
				" where tag_id in (".$tagIdList.")")->queryAll();
	
		$skuarray = array();
		foreach ($SkuInparams as $asku) {
			$skuarray[] = $asku['sku'];
		}
	
		$results = self::getStockDetail($skuarray, $sort, $order);
	
		return $results;
	}
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 品牌统计 返回汇总的品牌数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/27				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getBrandsInventory( $sort = 'brand_id', $order = 'asc', $defaultPageSize=20) {
		$conn=\Yii::$app->subdb;
	
		$DataCount=$conn->createCommand("select count(1) as dataCount from( select t.brand_id ".
				" from pd_brand t ".
				" left join pd_product t1 on t1.brand_id = t.brand_id ".
				" group by t.brand_id,t.name ) a ")->queryAll();
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount[0]['dataCount'],
				]);
	
		$data['pagination'] = $pagination;
		
		$brandDataArr = $conn->createCommand("select t.brand_id, t.name, count(t1.sku) count, group_concat(t1.sku SEPARATOR '\t') skuList ".
				" from pd_brand t ".
				" left join pd_product t1 on t1.brand_id = t.brand_id ".
				" group by t.brand_id,t.name ".
				" order by ".$sort." ".$order.
				" limit ".$pagination->limit." offset ".$pagination->offset)->queryAll();
	
		$index = $pagination->offset + 1;
		$rows = array();
	
		foreach ($brandDataArr as $brandData) {
			$row['id'] = $index;              /* '序号'不关联'brand_id'，关联简单编号 */
			$index++;                         /* 编号递增 */
			$row['brand_id'] = $brandData['brand_id'];
			$row['name'] = $brandData['name'];
				
			$skuList = explode("\t", $brandData['skuList']);
				
			$stock = self::getStock($skuList);
			// it returns like : array('stock'=>$total_stock, 'stock_value'=>$total_stock_value,'sku_count'=>$sku_count, 'skus'=>$sku_having_stock );
				
			$row['sku'] = $stock['sku_count'];//统计sku以getStock返回的为依据，而不以$allBrandData为依据
			$row['stock'] = $stock['stock'] == null ? '0' : $stock['stock'];
			$row['stock_value'] = $stock['stock_value'];
				
			$rows[] = $row;
		}
	
		$data['data'] = $rows;
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 品牌统计 返回汇总的品牌库存明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/27				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getBrandInventoryDetail($brand_id, $sort = 'stock', $order = 'desc') {
		$conn=\Yii::$app->subdb;
	
		$SkuInparams = $conn->createCommand("select sku ".
				" from pd_product ".
				" where brand_id=:brand_id ",[':brand_id'=>$brand_id])->queryAll();
	
		$skuarray = array();
		foreach ($SkuInparams as $asku) {
			$skuarray[] = $asku['sku'];
		}
	
		$results = self::getStockDetail($skuarray, $sort, $order);
	
		return $results;
	}
	
	/**
	 +----------------------------------------------------------
	 * 库存统计 商品数量及价值 返回数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/27				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getProductInventory($whID = 0 ,$sort = 'stock', $order = 'desc', $defaultPageSize=20) {
		$conn=\Yii::$app->subdb;
	
		$andSql="";
		$andParams="";
		 
		if ($whID != 0){
			$andSql .= " and t.warehouse_id=:warehouse_id ";
			$andParams[":warehouse_id"]=$whID;
		}
	
		$DataCount=$conn->createCommand("select count(1) as dataCount from( select t.sku ".
				" from wh_product_stock t ".
				" left join pd_product t1 on t.sku = t1.sku ".
				" left join wh_warehouse t2 on t.warehouse_id = t2.warehouse_id ".
				" where 1 ".$andSql.
				" group by t.sku,t1.name,t.warehouse_id ) a ",$andParams)->queryAll();
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount[0]['dataCount'],
				]);
	
		$data['pagination'] = $pagination;
	
		$data['data'] = $conn->createCommand("select t.sku, t1.name, sum(t.qty_in_stock) stock, sum(t.qty_in_stock * t.average_price) prices, group_concat(t2.name) wh_name ".
				" from wh_product_stock t ".
				" left join pd_product t1 on t.sku = t1.sku ".
				" left join wh_warehouse t2 on t.warehouse_id = t2.warehouse_id ".
				" where 1 ".$andSql.
				" group by t.sku,t1.name,t.warehouse_id ".
				" order by ".$sort." ".$order.
				" limit ".$pagination->limit." offset ".$pagination->offset,$andParams)->queryAll();
	
		$index = $pagination->offset + 1;
	
		foreach ($data['data'] as $key => $value){
			$data['data'][$key]['index'] = $index;
			$index++;
		}
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 返回销售店名数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/27				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getShop() {
		$conn=\Yii::$app->subdb;
	
		$shop = $conn->createCommand("select concat(order_source,'-',order_source_site_id) as sourceAndId ".
				" from od_order_v2 ".
				" group by order_source,order_source_site_id")->queryAll();
	
		return $shop;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计 标签统计 返回数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/29				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagSaleData($start, $end, $source = false, $siteID = false, $sort = 'id', $order = 'asc', $defaultPageSize=20) {
		$conn=\Yii::$app->subdb;
	
		$DataCount=$conn->createCommand("select count(1) as dataCount from( select t1.tag_name, t.tag_id, count(t.sku) count, group_concat(t.sku SEPARATOR '\t') skuList ".
				" from pd_product_tags t ".
				" join pd_tag t1 on t1.tag_id = t.tag_id ".
				" group by t.tag_id,t1.tag_name ) a")->queryAll();
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount[0]['dataCount'],
				]);
	
		$data['pagination'] = $pagination;
		
		$tagData = $conn->createCommand("select t1.tag_name, t.tag_id, count(t.sku) count, group_concat(t.sku SEPARATOR '\t') skuList ".
				" from pd_product_tags t ".
				" join pd_tag t1 on t1.tag_id = t.tag_id ".
				" group by t.tag_id,t1.tag_name ".
				" order by ".$sort." ".$order.
				" limit ".$pagination->limit." offset ".$pagination->offset)->queryAll();
	
		$index = $pagination->offset + 1;
	
		$result = array();
		$result['rows']=array();
	
		foreach ($tagData as $row) {
			$skuArray = explode("\t", $row['skuList']);
				
			$sales = self::getSalesDataBySKU($skuArray, $source, $siteID, $start, $end);
				
			$skuSoldArray = $sales['skuSoldArray'];
			
			$queryTmp2 = new Query;
			$queryTmp2->select("t1.brand_id")
			->from("pd_product t")
			->join("join", "pd_brand t1", "t.brand_id = t1.brand_id")
			->where(["in", "t.sku" ,$skuSoldArray])
			->groupBy("t1.brand_id");
				
			$brand = $queryTmp2->count("1", $conn);
			
			$result['rows'][] = array('id' => $index, 'tag_name' => $row['tag_name'],'skuSoldCount' => $sales['skuCount'], 'sku' => count($skuArray), 'volume' => $sales['volume'], 'brand' => $brand, 'price' => $sales['prices'], 'currency' => $sales['currency'],'tag_id' => $row['tag_id']);
			$index++;
		}
	
		$data['data'] = $result['rows'];
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  返回对应SKU组的价值
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/29				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	private static function getSalesDataBySKU($skuArray, $source, $siteID, $start, $end) {
		$conn=\Yii::$app->subdb;
	
		//make sure the start and end time is timestamp format, if free format, convert it
		if (strpos($start."","-" ) <> false or strpos($start.""," " ) <> false )
			$start = strtotime($start);
	
		if (strpos($end."","-" ) <> false or strpos($end.""," " ) <> false)
			$end = strtotime($end);
	
		$salesCurrency = 'CNY';
	
		//when there is no sku specified, return all zeros
		if (count($skuArray) == 0)
			return array('volume' => 0, 'prices' => 0, 'currency' =>$salesCurrency ,'skuCount' => 0 );
		
		$queryTmp = new Query;
		
		$queryTmp->select("sum( t1.quantity ) qty, sum(t1.price * t1.quantity) price, t.currency, t1.sku")
			->from("od_order_v2 t,od_order_item_v2 t1")
			->where(['and', 't.order_id = t1.order_id', 't.order_status >100', 't.order_status < 600',
					 ['in', 't1.sku', $skuArray], "t.paid_time>=".$start, "t.paid_time<=".$end]);
		
		if ( $source && $siteID) {
			$queryTmp->andWhere(['and', "t.order_source='$source'", "t.order_source_site_id='$siteID'"]);
		}
		elseif ($source && !$siteID) {
			$queryTmp->andWhere(['and', "t.order_source='$source'"]);
		}
	
		$queryTmp->groupBy("t1.sku,t.currency");
		
		$salesData = $queryTmp->createCommand($conn)->queryAll();
		$salesVolume = 0;
		$salesPrices = 0;
		$skuSoldArray = array();
	
		//所有统计以CNY作为返回结果
		foreach ($salesData as $item) {
			$salesVolume += $item['qty'];
			$salesPrices += Helper_Currency::convert($item['price'], 'CNY', $item['currency']);
			$skuSoldArray[] = $item['sku'];
		}
	
		$skuSoldArray  = array_unique($skuSoldArray);
		$skuSoldCount = count($skuSoldArray);
	
		return array('volume' => $salesVolume, 'prices' => $salesPrices, 'currency' => $salesCurrency ,'skuCount' => $skuSoldCount ,'skuSoldArray' => $skuSoldArray);
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  返回销售明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/30				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagSaleDetail($tag_id, $source, $site, $start, $end, $sort, $order) {
		$conn=\Yii::$app->subdb;
	
		$skuList = array();
	
		$sqlresult = $conn->createCommand("SELECT tag_id , group_concat(sku SEPARATOR '\t') skuList ".
				" FROM pd_product_tags ".
				" WHERE tag_id = $tag_id ")->queryAll();
	
		foreach ($sqlresult as $sqlrow){
			$skuList[] = $sqlrow['skuList'];
		}
	
		$skuDetail = self::getSalesOrdersBySKU($skuList, $source, $site, $start, $end, $sort, $order);
	
		return $skuDetail;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  根据传入的$skuList数组返回销售明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/30				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getSalesOrdersBySKU($skuList, $source, $siteID, $start, $end, $sort, $order, $defaultPageSize=5) {
		$conn=\Yii::$app->subdb;
	
		//make sure the start and end time is timestamp format, if free format, convert it
		if (strpos($start."","-" ) <> false or strpos($start.""," " ) <> false )
			$start = strtotime($start);
		if (strpos($end."","-" ) <> false or strpos($end.""," " ) <> false)
			$end = strtotime($end);
	
		//when there is no sku specified, return all zeros
		if (count($skuList) == 0){
			return array('order_id' => '', 'paid_time' => '', 'sku' => '', 'product_name' => '','prices' => '', 'quantity' => '', 'country' => '','order_source' =>'', 'brand' => '');
		}
		
		//传入来的值可能是字符串，可能数组
		if (gettype($skuList[0]) == "array"){
			$skuList = $skuList[0];
		}else{
			$skuList = explode("\t", $skuList[0]);
		}	
		
		$queryTmp = new Query;
		
		$queryTmp->select("t1.quantity, t1.price, t.consignee_country_code, t1.sku, t.order_id, t.order_source, t.order_source_site_id, t.paid_time, t.currency,t1.product_name")
			->from('od_order_v2 t,od_order_item_v2 t1')
			->where(['and', 't.order_id = t1.order_id', 't.order_status >100', 't.order_status < 600', 
					['in', 't1.sku', $skuList], "t.paid_time >= $start", "t.paid_time <= $end"])
			->groupBy('t1.sku,t.order_id,t.paid_time');
		
		if ( $source && $siteID) {
			$queryTmp->andWhere(['and', "t.order_source='$source'", "t.order_source_site_id='$siteID'"]);
		}
		elseif ( $source && !$siteID){
			$queryTmp->andWhere(['and', "t.order_source='$source'"]);
		}
		
		$DataCount = $queryTmp->count("1",$conn);
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount,
				]);
	
		$data['pagination'] = $pagination;
		
		$queryTmp->orderBy($sort." ".$order);
		$queryTmp->limit($pagination->limit);
		$queryTmp->offset($pagination->offset);
		
		$salesData = $queryTmp->createCommand($conn)->queryAll();
		
		$result = array();
		$result['rows'] = array();
	
		foreach ($salesData as $item) {
			$order_source = $item['order_source'].(($item['order_source_site_id'])?'-':'').$item['order_source_site_id'];
			$sku = $item['sku'];
			$product_name = $item['product_name'];
			
			$brandBySKU = $conn->createCommand("SELECT t.sku, t.prod_name_ch, t.brand_id, t1.name ".
					" FROM pd_product t ".
					" INNER JOIN pd_brand t1 ON t.brand_id = t1.brand_id AND t.sku = :sku",[":sku" => $sku])->queryAll();
				
			//产品无brand时,brand输出空值
			$brand = "";
			if (count($brandBySKU)>0) $brand = $brandBySKU[0]['name'];
				
			$salesPrices = 0;
			$salesPrices += Helper_Currency::convert($item['price'], 'CNY', $item['currency']);
			$result['rows'][] = array('order_id' => $item['order_id'], 'paid_time' => $item['paid_time'], 'sku' => $item['sku'], 'product_name' => $product_name,'prices' => $salesPrices*$item['quantity'], 'quantity' => $item['quantity'], 'country' => $item['consignee_country_code'],'order_source' => $order_source,'brand' => $brand, 'currency' => 'CNY', 'index' => '');
		}
	
		//SQL已做排序，故暂时隐藏    hqw 20150430
		// 		if(count($result['rows']) > 0){
		// 			self::$reportSort = $sort;
		// 			self::$reportOrder = $order;
		// 			usort($result['rows'], array('ReportHelper', 'reportSort'));
		// 		}
	
		$index = $pagination->offset + 1;
	
		foreach ($result['rows'] as $key => $value){
			$result['rows'][$key]['index'] = $index;
				
			$index++;
		}
	
		$data['data'] = $result['rows'];
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  多标签统计 根据查询条件返回SKU组合销售数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/30				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagsSalesData($ids, $start, $end, $source = false, $siteID = false, $tagNames) {
		$skuList = array();
		$idsStr="";
	
		foreach ($ids as $id) {
			$tempSkus = self::getSkuByTagID($id);
			foreach ($tempSkus as $aSKU){
				$skuList[] = $aSKU['sku'];
			}
		}
	
		$skuList = array_unique($skuList);
	
		$row = array();
		$row['sku'] = count($skuList);
	
	
		if (count($skuList) > 0) {
			$sales = self::getSalesDataBySKU($skuList, $source, $siteID, $start, $end);
			$row['volume'] = $sales['volume'];
			$row['prices'] = $sales['prices'];
			$row['currency'] = $sales['currency'];
			$row['skuSoldCount'] =  $sales['skuCount'];
		} else {
			$row['volume'] = 0;
			$row['prices'] = 0;
			$row['currency'] = 'CNY';
			$row['skuSoldCount'] = 0;
		}
	
		$row['tagIDs']=$ids;
		$row['params']['tagNames'] = $tagNames;
		$row['params']['start'] = date("Y-m-d H:m:s",$start);
		$row['params']['end'] =date("Y-m-d H:m:s",$end);
		if ($source) $row['params']['source'] = $source;
		if ($siteID) $row['params']['siteID'] = $siteID;
	
		$skulistToSearch = array($skuList);
		
		$row['detail'] = ReportHelper::getSalesOrdersBySKU($skulistToSearch, $source, $siteID, $start, $end, $sort='sku', $order='asc', 10000);
		$brandArray = array();
		for ($i = 0; $i < count($row['detail']['data']); $i++ ) {
			if ($row['detail']['data'][$i]['brand']) $brandArray[] = $row['detail']['data'][$i]['brand'];
		}
		$row['brands'] = count(array_unique($brandArray));
		return $row;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  多标签统计 返回显示明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/30				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getTagsSaleDetail($tagIDs, $source, $site, $start, $end, $sort, $order) {
		$skuList = array();
		$ids = explode(",",$tagIDs);
		foreach ($ids as $id) {
			$tempSkus = self::getSkuByTagID($id);
			foreach ($tempSkus as $aSKU)
				$skuList[] = $aSKU['sku'];
		}
	
		$skuList = array_unique($skuList);
		
		$skulistToSearch = array($skuList);
	
		$result = self::getSalesOrdersBySKU($skulistToSearch, $source, $site, $start, $end, $sort, $order);
	
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  品牌统计 返回数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/05/04				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getBrandsSalesData($start, $end, $source, $siteID, $sort = 'id', $order = 'asc', $defaultPageSize=5) {
		$conn=\Yii::$app->subdb;
	
		$DataCount=$conn->createCommand("select count(1) as dataCount from (select t.brand_id ".
				" from pd_brand t ".
				" left join pd_product t1 on t1.brand_id = t.brand_id ".
				" group by t.brand_id,t.name ) a")->queryAll();
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount[0]['dataCount'],
				]);
	
		$data['pagination'] = $pagination;
	
		$brandDataArr = $conn->createCommand("select t.brand_id, t.name, count(t1.sku) count, group_concat(t1.sku SEPARATOR '\t') skuList ".
				" from pd_brand t ".
				" left join pd_product t1 on t1.brand_id = t.brand_id ".
				" group by t.brand_id,t.name ".
				" order by ".$sort." ".$order.
				" limit ".$pagination->limit." offset ".$pagination->offset)->queryAll();
	
		$result = array();
		$result['rows'] = array();
	
		$index = $pagination->offset + 1;
	
		foreach ($brandDataArr as $brandData) {
			$skuList = explode("\t", $brandData['skuList']);
				
			$sales = self::getSalesDataBySKU($skuList, $source, $siteID, $start, $end);
	
			$result['rows'][] = array('id' => $index, 'name' => $brandData['name'], 'sku' => $brandData['count'],'skuSoldCount' => $sales['skuCount'], 'volume' => $sales['volume'], 'prices' => $sales['prices'], 'currency' => $sales['currency'], 'brand_id' => $brandData['brand_id']);
			$index++;
		}
	
		$data['data'] = $result['rows'];
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  品牌统计 返回显示明细数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/05/04				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getBrandSaleDetail($brand_id, $source, $site, $start, $end, $sort, $order) {
		$conn=\Yii::$app->subdb;
	
		$skuList = array();
	
		$sqlresult = $conn->createCommand("SELECT brand_id , group_concat(sku SEPARATOR '\t') skuList ".
				" FROM pd_product ".
				" WHERE brand_id = :brand_id",[":brand_id" => $brand_id])->queryAll();
	
		foreach ( $sqlresult as $sqlrow){
			$skuList[] = $sqlrow['skuList'];
		}
		
		$result = ReportHelper::getSalesOrdersBySKU($skuList, $source, $site, $start, $end, $sort, $order);
	
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 销售商品统计  商品数量及价值 返回查询数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/05/04				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public static function getProductSalesData($start, $end, $source, $siteID, $sort = 'sku', $order = 'asc', $defaultPageSize=20) {
		$conn=\Yii::$app->subdb;
	
		$andSql="";
		$andParams="";
			
		$andSql .= " and t.paid_time>=:start ";
		$andParams[":start"] = $start;
	
		$andSql .= " and t.paid_time<=:end ";
		$andParams[":end"] = $end;
	
		if ( $source && $siteID) {
			$andSql .= " and t.order_source=:order_source ";
			$andParams[":order_source"] = $source;
				
			$andSql .= " and t.order_source_site_id=:order_source_site_id ";
			$andParams[":order_source_site_id"] = $siteID;
		}else if($source && !$siteID){
			$andSql .= " and t.order_source=:order_source ";
			$andParams[":order_source"] = $source;
		}
	
		$DataCount=$conn->createCommand("select count(1) as dataCount from (".
				"select t1.sku, pd.name product_name, t.currency, sum(t1.quantity) sale, sum(t1.price * t1.quantity) price,count(t.order_id) ordered ".
				" from od_order_v2 t ".
				" left join od_order_item_v2 t1 on t.order_id = t1.order_id and t.order_status >100 and t.order_status <600 ".
				" left join pd_product pd on pd.sku = t1.sku ".
				" where t1.sku!='' ".$andSql.
				" group by t1.sku,t.currency ".
				") a",$andParams)->queryAll();
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount[0]['dataCount'],
				]);
	
		$data['pagination'] = $pagination;
	
		$data['data'] = $conn->createCommand("select t1.sku, pd.name product_name, t.currency, sum(t1.quantity) sale, sum(t1.price * t1.quantity) price,count(t.order_id) ordered ".
				" from od_order_v2 t ".
				" left join od_order_item_v2 t1 on t.order_id = t1.order_id and t.order_status >100 and t.order_status <600 ".
				" left join pd_product pd on pd.sku = t1.sku ".
				" where t1.sku!='' ".$andSql.
				" group by t1.sku,t.currency ".
				" order by ".$sort." ".$order.
				" limit ".$pagination->limit." offset ".$pagination->offset,$andParams)->queryAll();
	
		return $data;
	}
	
	
}

?>