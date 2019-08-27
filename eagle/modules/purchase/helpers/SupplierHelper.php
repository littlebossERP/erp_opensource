<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\purchase\helpers;

use eagle\modules\purchase\models\Supplier;
use eagle\modules\catalog\models\ProductSuppliers;

use yii;
use yii\data\Pagination;
use yii\base\Exception;
use eagle\models\catalog\Product;
use eagle\modules\util\helpers\TranslateHelper;


class SupplierHelper {

	//	1: 已启用
	//	2： 已停用
	const ACTIVE=1;
    const INACTIVE=2;
    protected static $SUPPLIER_STATUS = array(
    		"1" => "已启用",
    		"2" => "已停用",
    );
    
	protected static $productTopSupplierInfo=array();
	protected static $accountSettleMode=array("1"=>"月结","2"=>"付款后发货","3"=>"到货后付款");

	/**
	 * 检查是否存在已经停用的供应商
	 * @param
	 * @return array(),已经停用的id array
	 */
	public static function checkSupplierIdArrStatus($supplierIdArr)
	{	
		$unactiveIdArr=array();
		$unactiveNameArr=array();
		$rows = Yii::$app->get('subdb')->createCommand()
		->select('supplier_id,name')
		->from('pd_supplier u')
		->where(array('and', 'status='.self::INACTIVE, array('in', 'supplier_id',$supplierIdArr)))
		->queryAll();			
		
		foreach($rows as $row){
			$unactiveIdArr[]=$row['supplier_id'];
			$unactiveNameArr[]=$row['name'];
		}
		return array($unactiveIdArr,$unactiveNameArr);
	}
	
	/**
	 * 检查是否存在已经停用的供应商name
	 * @param
	 * @return array(),已经停用的id,name array
	 */
	public static function checkSupplierNameArrStatus($supplierNameArr)
	{
		$unactiveIdArr=array();
		$unactiveNameArr=array();
	
		$rows = Supplier::find()->where(['in','name',$supplierNameArr])->andwhere(['status'=>self::INACTIVE])->all();
		foreach($rows as $row){
			$unactiveIdArr[]=$row['supplier_id'];
			$unactiveNameArr[]=$row['name'];
		}
		return array($unactiveIdArr,$unactiveNameArr);
	}
	
	/**
	 * 创建默认供应商
	 */
	static public function createDefaultSupplier(){
		$defaultSupplier = new Supplier();
		$defaultSupplier->name ="(默认供应商)";
		$defaultSupplier->address_nation = "CN";
		$defaultSupplier->address_state = "未设置";
		$defaultSupplier->address_city = "未设置";
		$defaultSupplier->address_street = "未设置";
		$defaultSupplier->post_code = "未设置";
		$defaultSupplier->phone_number = "未设置";
		$defaultSupplier->fax_number = "未设置";
		$defaultSupplier->contact_name = "未设置";
		$defaultSupplier->mobile_number = "未设置";
		$defaultSupplier->qq = "未设置";
		$defaultSupplier->ali_wanwan = "未设置";
		$defaultSupplier->msn = "未设置";
		$defaultSupplier->email = "未设置";
		$defaultSupplier->payment_mode = "未设置";
		$defaultSupplier->payment_account = "未设置";
		$defaultSupplier->comment = "系统默认";
		$defaultSupplier->status = 1;
			
		$id = $defaultSupplier->save(false);
		if (!empty($id)){
			$defaultSupplier->supplier_id = 0;
			$defaultSupplier->save(false);
		}else {
			foreach ($defaultSupplier->errors as $k => $anError){
				$rtn['message'] .= "E_SPlierCrt ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}
	
	}
	
	/**
	 * 获取供应商状态
	 * @param
	 * @return
	 */	
	public static function getSupplierStatus($status_id='')
	{	//when there is status id passed, return all possible values
		if ($status_id == '')	$return_value = self::$SUPPLIER_STATUS;
		else{//when there is status_id passed, return the exactly one
			if (!isset(self::$SUPPLIER_STATUS["$status_id"]))
				$return_value = "(未定义)";
			else
				$return_value = self::$SUPPLIER_STATUS["$status_id"];
		}
		return 	$return_value;
	}	
	
	/**
	 * 获取所有供应商model
	 * @param	boolean 	include_disable(true/false)
	 * @return 	array
	 */
	static public function ListSupplierData($include_disable=false){
		$exists = Supplier::find()->andWhere(['supplier_id' => 0])->one();
		if (empty($exists)){
			self::createDefaultSupplier();
		}
		if($include_disable)
			$SupplierList = Supplier::find()->asArray()->all();
		else
			$SupplierList = Supplier::find()->where(['is_disable'=>0,'status'=>1])->asArray()->all();
		$result = array();
		foreach($SupplierList as $supplier){
			$result[$supplier['supplier_id']] = $supplier;
		}
		return $result;
	
	}//end of ListSupplierData
	
	/**
	 +----------------------------------------------------------
	 * 获取供应商列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param pageSize		每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				标签数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	static public function ListData($sort,$order,$page=1,$pageSize=20,$queryString=array()){
	
		$exists = Supplier::find()->andWhere(['supplier_id' => 0])->one();
		if (empty($exists)){
			self::createDefaultSupplier();
		}
	
		$query=Supplier::find()->Where(['is_disable'=>0]);
		if(!empty($queryString)){
			foreach ($queryString as $k=>$v){
				if(in_array($k, array('status','account_settle_mode','payment_mode')) ){
					$query->andWhere([$k=>$v]);
				}else{
					if($k=='address'){
						$query->andWhere(['like','address_nation',$v]);
						$query->andWhere(['like','address_state',$v]);
						$query->andWhere(['like','address_city',$v]);
						$query->andWhere(['like','address_street',$v]);
					}else
						$query->andWhere(['like',$k,$v]);
				}
			}
		}
	
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' =>$query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
	
		$result['data'] = $query->orderBy("$sort $order")
		->limit($pagination->limit)
		->offset($pagination->offset)
		->asArray()
		->all();
	
		return $result;
	}//end of ListData
	
	/**
	 +----------------------------------------------------------
	 * 获取供应商供应的产品 的 列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param pageSize		每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param supplier_id	供应商ID
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				标签数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	static public function supplierProductsListData($page, $pageSize, $sort, $order, $supplier_id, $keyword)
	{
		/*
		SELECT * FROM `pd_product` inner JOIN `pd_product_suppliers` ON pd_product.sku=pd_product_suppliers.sku and pd_product_suppliers.supplier_id=101
		*/
		$sql="SELECT p.name, p.type,p.status,p.prod_name_ch,p.prod_name_en,p.brand_id,p.purchase_by,p.photo_primary,p.comment,p.product_id,ps.* 
				FROM  pd_product p 
				INNER JOIN pd_product_suppliers ps
				ON p.sku=ps.sku and ps.supplier_id=:supplier_id ";
		$bindParmValues = array();
		$bindParmValues["supplier_id"] = $supplier_id;
		if($keyword!=='')
		{
			$sql .= " and (p.sku like :keyword or p.name like :keyword or p.prod_name_ch like :keyword or p.prod_name_en like :keyword) ";
			$bindParmValues["keyword"] = "%".$keyword."%";
		}
		$command_all = Yii::$app->get('subdb')->createCommand($sql);
		//bind the parameter values
		foreach ($bindParmValues as $k=>$v){
			$bindTarget = trim(":".$k);
			if($k=='supplier_id')
				$command_all->bindValue($bindTarget, ($v), \PDO::PARAM_INT);
			else
				$command_all->bindValue($bindTarget, ($v), \PDO::PARAM_STR);
		}
		
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' =>count($command_all->queryAll()),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		$sql.="order by ps."."$sort $order limit $pagination->offset , $pagination->limit";
		$command = Yii::$app->get('subdb')->createCommand($sql);
		//bind the parameter values
		foreach ($bindParmValues as $k=>$v){
			$bindTarget = trim(":".$k);
			if($k=='supplier_id')
				$command->bindValue($bindTarget, ($v), \PDO::PARAM_INT);
			else
				$command->bindValue($bindTarget, ($v), \PDO::PARAM_STR);
		}
		$result['data'] = $command->queryAll();
		$result['sql'] = $command->getRawSql();
		return $result;
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 根据供应商名字找出供应商编号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $supplier_name		供应商名字
	 * @param $AutoAdd				自动增加
	 +----------------------------------------------------------
	 * @return		array
	 * 	boolean			success  执行结果
	 * 	string			message  执行失败的提示信息
	 * 	int				supplier_id 供应商编号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/03/16				初始化
	 +----------------------------------------------------------
	 **/
	static public function getSupplierId($supplier_name , $AutoAdd = FALSE){
		try {
			$result['success'] = true;
			$result['message'] = '';
			$result['supplier_id'] = 0;
			$supplier = Supplier::findOne(['name'=>$supplier_name]);
			if (!empty($supplier)){
				$result['supplier_id'] = (!is_numeric($supplier->supplier_id)?"":$supplier->supplier_id);
				return $result;
			}else{
				// 自动插入Supplier
				if ($AutoAdd){
					$supplier = new Supplier();
					$supplier->name = $supplier_name;
					$supplier->create_time = date("Y-m-d H:i:s");
					$supplier->capture_user_id = Yii::$app->user->id;
					$supplier->status = 1;
					$insert_rt = $supplier->insert();
	
					if ($insert_rt){
						//成功添加后重新获取 Supplier id
						$tmp_rt = self::getSupplierId($supplier_name);
						if ($tmp_rt['success']){
							//成功直接 返回 , 否则按失败返回
							$result['supplier_id'] = $tmp_rt['supplier_id'];
							return $result;
						}
					}
	
					$result['success'] = false;
					$result['message'] = '后台添加供应商失败';
					return $result;
				}
			}
		} catch (Exception $e) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
			return $result;
		}
	
	}//end of getSupplierId
	
	/**
	 * Get the top supplier for each product. Return the map , with sku key  
	 * @param
	 * @return array("sku1"=>array("supplier_id"=>"","name"=>"","purchase_price"=>""),"sku2"=>.......
	 */
	public static function getProductTopSupplierInfo()
	{
		if (count(self::$productTopSupplierInfo)>0) return self::$productTopSupplierInfo;
		
		$rows=Yii::$app->get('subdb')->createCommand('SELECT ps.sku as sku,su.supplier_id as supplier_id,su.name as name,ps.purchase_price as purchase_price FROM pd_product_suppliers ps,pd_supplier su where ps.supplier_id=su.supplier_id and ps.priority=0 and su.is_disable=0 and su.status=1')->queryAll();
		//$rows=Yii::app()->subdb->createCommand('SELECT sku,su.supplier_id as supplier_id,su.name as name,purchase_price FROM pd_product pd INNER JOIN pd_supplier su ON ( pd.supplier_id=su.supplier_id)')->queryAll();
		
		foreach($rows as $row) {
			$myArr=array();
			$myArr["supplier_id"]=$row['supplier_id'];
			$myArr["name"]=$row['name'];
			$myArr["purchase_price"]=$row['purchase_price'];
			self::$productTopSupplierInfo[$row['sku']]=$myArr;
		}		
		return self::$productTopSupplierInfo;		
	}
	
	/**
	 * 获取指定sku的产品的所有供应商的名称和价格信息	  
	 * @param  $sku
	 * @return 
	 */	
	public static function getSuppliersInfoForOneProduct($sku){
		//$suppliersInfo=array();
		$rows=Yii::$app->get('subdb')->createCommand('SELECT su.supplier_id as supplier_id,name,purchase_price FROM pd_product_suppliers pds INNER JOIN pd_supplier su ON ( pds.supplier_id=su.supplier_id AND su.is_disable=0 AND su.status=1 ) WHERE sku=:sku')
		                       ->bindParam(":sku",$sku,\PDO::PARAM_STR)->queryAll();
		return $rows;
	}
	
	/**
	 * 结算方式。
	 * @param
	 * @return 
	 */	
	public static function getAccountSettleMode($mode_id='')
	{	//when there is no mode id passed, return all possible values
		$rtn_value = "";
		if ($mode_id == '')
			$rtn_value = self::$accountSettleMode;
		else{//when there is $mode_id passed, return the exactly one
			if (!isset(self::$accountSettleMode["$mode_id"])){
				//InventoryInterface::SysLog_Create("purchase",__CLASS__, __FUNCTION__,"","try to return mode_id is $mode_id ".(isset(self::$accountSettleMode["$mode_id"]) ? self::$accountSettleMode["$mode_id"] : " but not isset"), "trace");
				$rtn_value = "(Undefined)";
			}else
				$rtn_value = self::$accountSettleMode["$mode_id"];			
		}
		return 	$rtn_value;
	}
	
	/**
	 * 获取所有供应商信息。
	 * @param
	 * @return
	 */	
	public static  function getAllSuppliersInfo()
	{
	    $rows=Yii::app()->subdb->createCommand('SELECT * FROM `pd_supplier` ')->queryAll();
	    return $rows;
	}

	public static  function getAllSuppliersIdName($mode="row")
	{
		$rows=Yii::$app->get('subdb')->createCommand('SELECT supplier_id,name FROM `pd_supplier` WHERE `is_disable`=0 AND `status`=1 ')->queryAll();
		if ($mode=="row") {
			return $rows;
		}		
		// mode==map
		$result=array();
		foreach($rows as $row)	{
			$result[(string)$row['supplier_id']]=$row['name'];
		}
		return $result;
	}	
	
	/**
	 * set the $supplierId the top supplier for the product $sku. !!! No more refering to  database table  "pd_product" and "pd_product_suppliers"
	 * If the $supplierId and the $price makes no difference for the $sku, there is no need to update anything,just return.
	 * @param $sku,$supplierId,$price
	 * $price==0,  just return 
	 * @return true or false
	 */	
	public static function updateProductSupplierInfo($sku,$supplierId,$price) {
		if ($price<0) return false;
		// when price==0  that is special case
		if ($price==0) return true;  
		
		$isExistSupplier=false;
		$existSupplierObject=null;
		$existSupplierPriority=-1;
		
		
		$productSupplierCollection=ProductSuppliers::findAll(['sku'=>$sku]);
		foreach($productSupplierCollection as $productSupplier) {
			if ($productSupplier->priority==0 and $productSupplier->supplier_id==$supplierId and $productSupplier->purchase_price==$price)  return true; // no difference,no need to update,just return
				
			if ($productSupplier->supplier_id==$supplierId) {
				$isExistSupplier=true;
				$existSupplierPriority=$productSupplier->priority;
				$existSupplierObject=$productSupplier;
				break;
			}
		}
	
	
		if ($isExistSupplier){
			if ($existSupplierPriority==0){
				//only need to update the price
				$existSupplierObject->purchase_price=$price;
				if (!$existSupplierObject->save(false)) return false;
			} else {
				//The supplierid exists and priority>0  ,not the first option.
				//It needs to be exchanged the priority with the current top supplier.
				foreach($productSupplierCollection as $productSupplier) {
					if ($productSupplier->supplier_id==$supplierId) {
						$productSupplier->priority=0;
						$productSupplier->purchase_price=$price;
						if (!$productSupplier->save(false)) return false;
					}else if ($productSupplier->priority==0){
						$productSupplier->priority=$existSupplierPriority;
						if (!$productSupplier->save(false)) return false;
					}
				}
			}
		} else {		
	    	//The supplierid does not exist. 
		    //The current suppliers for the product needs to postpone the priority to the latter one ,and  the chosen supplier setted to the top
			foreach($productSupplierCollection as $productSupplier) {
				if ($productSupplier->priority==4) {
					$productSupplier->delete();
				}else{
					$productSupplier->priority=$productSupplier->priority+1;
					if (!$productSupplier->save(false)) return false;
				}
			}
			$productSupplier=new ProductSuppliers();
			$productSupplier->purchase_price=$price;
			$productSupplier->supplier_id=$supplierId;
			$productSupplier->sku=$sku;
			$productSupplier->priority=0;
			if (!$productSupplier->save(false)) return false;
		}
		 		
		//  pd_product需要和pd_product_suppliers数据表的信息保持一致。 上面设置了pd_product_suppliers， 这里需要设置pd_product数据表的supplier_id。
		
		$product=Product::find()->where('sku=:sku', array(':sku'=>$sku))->one();
		if ($product==null)  return false;
		$product->supplier_id=$supplierId;
		$product->purchase_price=$price;
		$product->save(false);
		
		return true;		
	}
	
	/**
	 * 删除供应商
	 * @param 	string	$supplierId
	 * @return boolean
	 */
	public static function deleteSupplier($supplierId){
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		try{
			$ids=explode(",", $supplierId);
			//update ProductSuppliers priority
			self::updateProductSuppliersWhenSupplierInactive($ids);
			//del product_suppliers
			ProductSuppliers::deleteAll("supplier_id in (".$supplierId.")");
			//del Suppliers
			Supplier::deleteAll(['in','supplier_id',$ids]);
	
			$transaction->commit();
			return (array('success'=>true,'message'=>''));
		}
		catch(Exception $e){
			$transaction->rollBack();
			return (array('success'=>false,'message'=>$e->getMessage()));
		}
	
	}
	
	/**
	 * 停用供应商
	 * @param 	string	$supplierId
	 * @return boolean
	 */
	public static function inactivateSupplier($supplierId){
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		try{
			$ids=explode(",", $supplierId);
			//update ProductSuppliers priority
			self::updateProductSuppliersWhenSupplierInactive($ids);
			//del product_suppliers
			ProductSuppliers::deleteAll("supplier_id in (".$supplierId.")");
			//inactivate Suppliers
			Supplier::updateAll(['status'=>2],['in','supplier_id',$ids]);
			
			$transaction->commit();	
			return (array('success'=>true,'message'=>''));
		}
		catch(Exception $e){
			$transaction->rollBack();
			return (array('success'=>false,'message'=>$e->getMessage()));
		}
	}
	
	/**
	 * 重新启用供应商
	 * @param 	string	$supplierId
	 * @return boolean
	 */
	public static function activateSupplier($supplierId){
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		try{
			$ids=explode(",", $supplierId);
			//activate Suppliers
			Supplier::updateAll(['status'=>1],['in','supplier_id',$ids]);
			$transaction->commit();
			return (array('success'=>true,'message'=>''));
		}
		catch(Exception $e){
			$transaction->rollBack();
			return (array('success'=>false,'message'=>$e->getMessage()));
		}
	}
	
	/**
	 * 停用或删除供应商前，重设产品 被删除供应商外的 供应商首选顺序
	 * @param 	string	$supplierId
	 * @return boolean
	 */
	public static function updateProductSuppliersWhenSupplierInactive($supplier_ids){
		$pdSuppliers =ProductSuppliers::find()->select('sku')
			->distinct(true)
			->where(['in','supplier_id',$supplier_ids])
			->asArray()
			->all();
		foreach ($pdSuppliers as $row){
			$sku=$row['sku'];
	
			$sku_pd_suppliers=ProductSuppliers::find()->where(['sku'=>$sku])->andWhere(['not in','supplier_id',$supplier_ids])->orderBy('priority ASC')->all();
				
			$priority=0;
			if(count($sku_pd_suppliers)>0){
				foreach ($sku_pd_suppliers as $model){
					$model->priority = $priority;
					$priority++;
					//update ProductSuppliers model
					$model->save(false);
					if ($model->priority==0){
						//reset product yop supplier when ProductSuppliers model updated
						Product::updateAll(['supplier_id'=>$model->supplier_id,'purchase_price'=>$model->purchase_price],"sku=:sku",[':sku'=>$sku]);
					}
				}
			}else{
				Product::updateAll(['supplier_id'=>0],"sku=:sku",[':sku'=>$sku]);//无其他供应商时，set为默认供应商
			}
		}
	}
	
	/**
	 * 供应商删除产品供应
	 * @param 	string	$supplierId
	 * @return boolean
	 */
	public static function supplierRemoveProduct($supplier_id,$skus_encode){
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		try{
			$skus_encode=explode(",", $skus_encode);
			$skus=array();
			foreach ($skus_encode as $encode){
				$sku=base64_decode($encode);
				if($sku!=='') $skus[]=$sku;
			}
			//del product_suppliers
			ProductSuppliers::deleteAll(['and',['in','sku',$skus],['supplier_id'=>$supplier_id]]);

			//update ProductSuppliers priority
			self::updateProductSuppliersWhenPdSupplierRemove($skus);
			
			$transaction->commit();
			return (array('success'=>true,'message'=>''));
		}
		catch(Exception $e){
			$transaction->rollBack();
			return (array('success'=>false,'message'=>$e->getMessage()));
		}
	}
	
	/**
	 * 删除产品供应商时，重设产品 剩余供应商的 供应商首选顺序
	 * @param 	array	$skus
	 * @return boolean
	 */
	public static function updateProductSuppliersWhenPdSupplierRemove($skus){
		foreach ($skus as $sku){
			if(trim($sku)!==''){
				$sku_pd_suppliers=ProductSuppliers::find()->where(['sku'=>$sku])->orderBy('priority ASC')->all();
				$priority=0;
				foreach ($sku_pd_suppliers as $model){
					$model->priority = $priority;
					$priority++;
					//update ProductSuppliers model
					$model->save(false);
					if ($model->priority==0){
						//reset product yop supplier when ProductSuppliers model updated
						Product::updateAll(['supplier_id'=>$model->supplier_id,'purchase_price'=>$model->purchase_price],"sku=:sku",[':sku'=>$sku]);
					}
				}
			}
		}
	}
	
	/**
	 * 获得产品报价信息
	 * @param 	int		page		当前页
	 * @param	int		pageSize	每页显示记录数
	 * @param	string	sort		排序条件
	 * @param	string	order		升降序
	 * @param	boolen	hasPriceOnly是否只查询设置了采购价的记录
	 * @param	int		brand_id	产品品牌id
	 * @param	int		supplier_id	供应商id
	 * @param	string	keyword		sku关键字
	 * @return 	array
	 */
	public static function listProductSupplierDatas($page,$pageSize,$sort,$order,$hasPriceOnly=false,$brand_id,$supplier_id,$keyword){
		$result=array();
		$result['data']=array();

		$query=Product::find()->where("1");
		if($brand_id!=='' && is_numeric($brand_id))
			$query->andWhere(['brand_id'=>$brand_id]);
		if($keyword!=='')
			$query->andWhere(['like','sku',$keyword]);
			
		$query->orderBy("$sort $order");
		
		if($hasPriceOnly){//只显示有报价产品
			if($supplier_id!=='')
				$pd_condition="supplier_id=$supplier_id";
			else 
				$pd_condition='1';
			$query->andWhere("`sku` in (select `sku` from `pd_product_suppliers` where $pd_condition)");
			
			$pagination = new Pagination([
					'pageSize' => $pageSize,
					'totalCount' =>$query->count(),
					'pageSizeLimit'=>[5,200],//每页显示条数范围
					]);
			$result['pagination'] = $pagination;
			$pd_datas = $query->offset($pagination->offset)
				->limit($pagination->limit)
				->asArray()->all();
			foreach ($pd_datas as $data){
				$sku=$data['sku'];
				$tmp = $data;
				$tmp['pd_supplier_info']=array();
				//$pd_supplier=array();
				$pd_supplier=ProductSuppliers::find()->where(['sku'=>$sku])
					->andWhere("`supplier_id`<>0 or (`supplier_id`=0 and `purchase_price`<>0)")
					->orderBy("priority ASC")
					->asArray()->all();
				$tmp['pd_supplier_info']=$pd_supplier;
				
				$result['data'][]=$tmp;
			}
		}else{
			if($supplier_id!=='')
				$query->andWhere("`sku` in (select `sku` from `pd_product_suppliers` where supplier_id=$supplier_id )");
			
			$pagination = new Pagination([
					'pageSize' => $pageSize,
					'totalCount' =>$query->count(),
					'pageSizeLimit'=>[5,200],//每页显示条数范围
					]);
			$result['pagination'] = $pagination;
			$pd_datas = $query->offset($pagination->offset)
				->limit($pagination->limit)
				->asArray()->all();
			foreach ($pd_datas as $data){
				$sku=$data['sku'];
				$tmp = $data;
				$tmp['pd_supplier_info']=array();
				//$pd_supplier=array();
				$pd_supplier=ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->asArray()->all();
				$tmp['pd_supplier_info']=$pd_supplier;
				
				$result['data'][]=$tmp;
			}
		}
		
		/*
		
		
		if($hasPriceOnly){//只显示有报价产品
			foreach ($pd_datas as $data){
				$sku=$data['sku'];
				$data['pd_supplier_info']=array();
				
				if($supplier_id!==''){
					$hasThisPdSupplier = ProductSuppliers::findOne(['supplier_id'=>$supplier_id,'sku'=>$sku]);
					if($hasThisPdSupplier!==null){
						$pd_supplier=ProductSuppliers::find()->where(['sku'=>$sku])
															->andWhere(['not',['supplier_id'=>0]])
															->orWhere(['and','supplier_id=0','purchase_price<>0'])
															->orderBy("priority ASC")
															->asArray()->all();
					}else continue;
				}else{
					$pd_supplier=ProductSuppliers::find()->where(['sku'=>$sku])
															->andWhere(['not',['supplier_id'=>0]])
															->orWhere(['and','supplier_id=0','purchase_price<>0'])
															->orderBy("priority ASC")
															->asArray()->all();
				}
				$data['pd_supplier_info']=$pd_supplier;
				if(count($data['pd_supplier_info'])>0)
					$result['data'][]=$data;
			}
			$pagination = new Pagination([
					'pageSize' => $pageSize,
					'totalCount' =>count($result['data']),
					'pageSizeLimit'=>[5,200],//每页显示条数范围
					]);
			$result['pagination'] = $pagination;
			
			$result['data'] = array_slice($result['data'],$pagination->offset,$pagination->limit);
		}else{//显示所有产品，无供应商则为默认，price=0
			$pagination = new Pagination([
					'pageSize' => $pageSize,
					'totalCount' =>count($pd_datas),
					'pageSizeLimit'=>[5,200],//每页显示条数范围
					]);
			$result['pagination'] = $pagination;

			$pd_datas = array_slice($pd_datas,$pagination->offset,$pagination->limit);
			foreach ($pd_datas as $data){
				$sku=$data['sku'];
				$data['pd_supplier_info']=array();
				
				if($supplier_id!==''){
					$hasThisPdSupplier = ProductSuppliers::findOne(['supplier_id'=>$supplier_id,'sku'=>$sku]);
					if($hasThisPdSupplier!==null){
						$pd_supplier=ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->asArray()->all();
					}else continue;
				}else{
					$pd_supplier=ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->asArray()->all();
				}
				
				$data['pd_supplier_info']=$pd_supplier;
				$result['data'][]=$data;
			}
		}
		*/
		return $result;
	}
	
	/**
	 * 删除指定产品报价
	 * @param 	string		skus_encode				encode后的sku字符串，以‘,’连接
	 * @param	string		product_supplier_ids	product_supplier_id字符串，以‘,’连接
	 * @return 	array		操作结果
	 */
	public static function removeProductSupplier($skus_encode,$product_supplier_ids){
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		try{
			$skus_encode=explode(",", $skus_encode);
			$skus=array();
			foreach ($skus_encode as $encode){
				$sku=base64_decode($encode);
				if($sku!=='') $skus[]=$sku;
			}
			$product_supplier_ids = explode(",", $product_supplier_ids);
			$product_supplier_id_arr=array();
			foreach ($product_supplier_ids as $id){
				if($id!=='') $product_supplier_id_arr[]=$id;
			}
			
			//del product_suppliers
			ProductSuppliers::deleteAll(['in','product_supplier_id',$product_supplier_id_arr]);
		
			//update ProductSuppliers priority
			self::updateProductSuppliersWhenPdSupplierRemove($skus);
				
			$transaction->commit();
			return (array('success'=>true,'message'=>''));
		}
		catch(Exception $e){
			$transaction->rollBack();
			return (array('success'=>false,'message'=>$e->getMessage()));
		}
	}
	
	/**
	 * 保存用户通过采购模块报价页面手动录入的供应商报价
	 * @param 	array		$pdSupplierData=array('supplier_id'=>1,'prod'=>array('priority'=>0,'new_price'=>10.00))
	 * 						supplier_id:供应商id；priority:优先级；new_price:新报价
	 * @return 	array		操作结果
	 */
	public static function savePdSuppliers($pdSupplierData){
		$result['success']=true;
		$result['message']='';
		
		$transaction = Yii::$app->get('subdb')->beginTransaction();

		$supplier_id = $pdSupplierData['supplier_id'];
		
		foreach ($pdSupplierData['prod'] as $row){
			$sku = trim($row['sku']);
			$priority = trim($row['priority']);
			$purchase_price = trim($row['new_price']);
			$rtn=self::updateOrResortProductSupplier($sku, $supplier_id, $purchase_price,$priority);
			
			if(!$rtn['success'])
				$result['success']=false;
			$result['message'].=$rtn['message'];
		}
		
		if($result['success']){
			$transaction->commit();
		}else{
			$transaction->rollBack();
		}
		return $result;
	}
	
	/* 更新报价
	 * 		当供应商报价已存在，优先级相同时，仅更新该供应商价格
	 * 		当供应商报价已存在，优先级不同时，放弃原优先级，打尖到指定优先级并重置其他报价优先级
	 * 		当供应商报价不存在，直接覆盖该优先级报价
	 * @param	$sku
	 * @param	$supplier_id
	 * @param	$price
	 * @param	$priority
	 * @return	array
	 */
	public static function updateOrResortProductSupplier($sku, $supplier_id, $price,$priority){
		$result['success']=true;
		$result['message']='';
		if ($price<0){
			$result['success']=false;
			$result['message']=TranslateHelper::t('价格必须大于等于0');
			return $result;
		}
		
		$isExistSupplier=false;
		$existSupplierObject=null;
		$existSupplierPriority=-1;
		
		$productSupplierCollection=ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->all();
		foreach($productSupplierCollection as $productSupplier) {
			if ($productSupplier->priority==$priority and $productSupplier->supplier_id==$supplier_id and $productSupplier->purchase_price==$price)
			{
				$result['message']=TranslateHelper::t('报价信息没变化，不需要保存');
				return $result; // no difference,no need to update,just return
			}
			if ($productSupplier->supplier_id==$supplier_id) {
				$isExistSupplier=true;
				$existSupplierPriority=$productSupplier->priority;
				$existSupplierObject=$productSupplier;
				break;
			}
		}
		

		if ($isExistSupplier){
			if ($existSupplierPriority==$priority){
				//only need to update the price
				$existSupplierObject->purchase_price=$price;
				if (!$existSupplierObject->save()){
					$result['success']=false;
					foreach ($existSupplierObject->errors as $k => $anError){
						$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}
			} else {
				//当existPriority小于newPriority，即该供应商优先级下调
				if($existSupplierPriority<$priority){
					ProductSuppliers::deleteAll(['sku'=>$sku,'priority'=>$existSupplierPriority,'supplier_id'=>$supplier_id]);
					for($i=$existSupplierPriority; $i<=$priority; $i++){
						foreach ($productSupplierCollection as &$productSupplier){
							//当原来的报价优先级处于existPriority和newPriority之间，则优先级自动进1；
							if($productSupplier->priority==$i){
								$productSupplier->priority=$i-1;
								$productSupplier->save(false);
								unset($productSupplier);
								break;
							}
						}
					}
				}
				//当existPriority大于newPriority，即该供应商优先级上升
				if($existSupplierPriority>$priority){
					for($i=$existSupplierPriority; $i>=$priority; $i--){
						ProductSuppliers::deleteAll(['sku'=>$sku,'priority'=>$existSupplierPriority,'supplier_id'=>$supplier_id]);
						foreach ($productSupplierCollection as &$productSupplier){
							//当原来的报价优先级处于existPriority和newPriority之间，则优先级自动退1；
							if($productSupplier->priority==$i){
								$productSupplier->priority=$i+1;
								$productSupplier->save(false);
								unset($productSupplier);
								break;
							}
						}
					}
				}
				//保存目标报价
				$productSupplier=new ProductSuppliers();
				$productSupplier->purchase_price=$price;
				$productSupplier->supplier_id=$supplier_id;
				$productSupplier->sku=$sku;
				$productSupplier->priority=$priority;
				if (!$productSupplier->save()){
					$result['success']=false;
					foreach ($productSupplier->errors as $k => $anError){
						$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}
			}
		} else {
			//The supplierid does not exist.
			$priorityUsed = false;
			//if this priority is used,cover it
			foreach($productSupplierCollection as $productSupplier) {
				if ($productSupplier->priority==$priority) {
					$priorityUsed = true;
					$productSupplier->purchase_price=$price;
					$productSupplier->supplier_id=$supplier_id;

					if (!$productSupplier->save()){
						$result['success']=false;
						foreach ($productSupplier->errors as $k => $anError){
							$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
						}
					}
				}
			}
			//if this priority is not used,create a new one
			if(!$priorityUsed){
				$productSupplier=new ProductSuppliers();
				$productSupplier->purchase_price=$price;
				$productSupplier->supplier_id=$supplier_id;
				$productSupplier->sku=$sku;
				$productSupplier->priority=$priority;
				if (!$productSupplier->save()){
					$result['success']=false;
					foreach ($productSupplier->errors as $k => $anError){
						$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}
			}
		}
		
		//如果$priority=0，需要同步pd_product表中的供应商信息
		if($priority==0){
			$product=Product::findOne(['sku'=>$sku]);
			if ($product==null)  return array('success'=>false,'message'=>TranslateHelper::t("产品$sku不存在，保存终止"));
			$product->supplier_id=$supplier_id;
			$product->purchase_price=$price;
			
			if( $product->prod_width == '0.00')
				$product->prod_width = 0;
			if( $product->prod_length == '0.00')
				$product->prod_length = 0;
			if( $product->prod_height == '0.00')
				$product->prod_height = 0;
			
			//$product->save(false);
			if (!$product->save()){
				$result['success']=false;
				foreach ($product->errors as $k => $anError){
					$result['message'] .= ($result['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
		}
		
		return $result;
	}
}

?>
