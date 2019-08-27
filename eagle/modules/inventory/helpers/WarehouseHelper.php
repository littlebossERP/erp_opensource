<?php
/**
 +------------------------------------------------------------------------------
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 +------------------------------------------------------------------------------
*/

/**
 +------------------------------------------------------------------------------
 * 仓储模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Inventory
 * @package		Controller/Inventory
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
*/
namespace eagle\modules\inventory\helpers;

use eagle\modules\util\helpers\GetControlData;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper;
use yii\data\Pagination;
use yii;
use eagle\modules\inventory\models\WarehouseCoverNation;
use eagle\modules\inventory\models\ProductStock;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SysCountry;
use common\helpers\Helper_Array;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\models\Product;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\OdOrder;
use eagle\modules\permission\helpers\UserHelper;

class WarehouseHelper{
	/**
	 +---------------------------------------------------------------------------------------------
	 + Below are Const definition for this module
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	protected static $ACTIVE_STATUS = array(
			"Y" => "有效启用",
			"N" => "无效已关闭",
			"All" => "全部"
	);
	
	
	protected static $IS_OVERSEA = array(
			"0" => "本地自营仓库",
			"1" => "海外仓"
	);

	public static function getIsOversea($id=''){
		if (is_numeric($id) or $id=="")
			return GetControlData::getValueFromArray(self::$IS_OVERSEA, $id);
		else
			return GetControlData::getValueFromArray(array_flip(self::$IS_OVERSEA), $id);
	}
	/**
	 * 返回国家区域中文名称
	 */
	public static function countryRegionChName(){
		return array(
			'Africa'=>TranslateHelper::t('非洲'),
			'Asia'=>TranslateHelper::t('亚洲'),
			'Central America and Caribbean'=>TranslateHelper::t('中美洲和加勒比海'),
			'Europe'=>TranslateHelper::t('欧洲'),
			'Middle East'=>TranslateHelper::t('中东'),
			'North America'=>TranslateHelper::t('北美洲'),
			'Oceania'=>TranslateHelper::t('大洋洲'),
			'South America'=>TranslateHelper::t('南美洲'),
			'Southeast Asia'=>TranslateHelper::t('东南亚'),
				
		);
	}
	
	/**
	 * 返回国家代码对应的中/英文国家model
	 */
	public static function getCountryInfoByCode($code){
		$countryModel = SysCountry::findOne(['country_code'=>$code]);
		return $countryModel;
	}
	/**
	 * 返回国家名称对应的国家代码名称
	 */
	public static function getCountryInfoByName($name){
		$countryModel = SysCountry::find()->where(['or',['country_en'=>$name],['country_zh'=>$name]])->one();
		return $countryModel;
	}
	
	/**
	 * 返回国家代码对应的中/英文国家名称,用于combobox
	 */
	public static function countryComboBoxData(){
		$result = array();
		$countryModel = SysCountry::find()->asArray()->all();
		foreach ($countryModel as $country){
			if($country['country_code'] =='GB'){
				$result['英国(GB)'] = $country['country_code'];
			}
			elseif($country['country_code'] =='UK'){
				$result['英国(UK)'] = $country['country_code'];
			}
			else{
				$result[$country['country_zh']] = $country['country_code'];
			}
			$result[$country['country_en']] = $country['country_code'];
		}
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取StockChangeType列表数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id			active status id
	 +---------------------------------------------------------------------------------------------
	 * @return				array of StockChangeType information
	 *                      if @parm id = '', return all StockChangeType
	 *                      Other wise, return the particular StockChangeType info only
	 *                      (@parm id can be either the key id or the label)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getActiveStatus($id=''){
		if (is_numeric($id) or $id=="")
			return GetControlData::getValueFromArray(self::$ACTIVE_STATUS, $id);
		else
			return GetControlData::getValueFromArray(array_flip(self::$ACTIVE_STATUS), $id);
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get active status id and Name Combo box format
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			map.
	 *			e.g.
	 *			array( array('is_active'=>'Y','name'=>"正常开启"),
	 *				   array (...)
	 *				)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function activeStatusIdNameComoBox(){
		$activeStatusIdNameArr=array();
		$activeStatusIdNameMap=self::getActiveStatus();
		foreach($activeStatusIdNameMap as $id=>$name)
		{
			$activeStatusIdNameArr[]=array('is_active'=>$id,'name'=>$name);
		}
		$activeStatusIdNameComoBox=$activeStatusIdNameArr;
		return $activeStatusIdNameComoBox;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * To get active status id and Name Combo box format
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			map.
	 *			e.g.
	 *			array( array('is_active'=>'Y','name'=>"正常开启"),
	 *				   array (...)
	 *				)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function isOverseaComoBox(){
		$activeStatusIdNameArr=array();
		$activeStatusIdNameMap=self::getIsOversea();
		foreach($activeStatusIdNameMap as $id=>$name)
		{
			$activeStatusIdNameArr[]=array('is_oversea'=>$id,'name'=>$name);
		}
		$activeStatusIdNameComoBox=$activeStatusIdNameArr;
		return $activeStatusIdNameComoBox;
	}	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To list all Warehouse Data
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param page			Page Number to be shown
	 * @param rows			number of rows per page
	 * @param sort          sort by which field
	 * @param order         order by which field
	 * @param queryString   array of criterias
	 +---------------------------------------------------------------------------------------------
	 * @return				To List all warehouse data.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listWarehouseData($page, $rows, $sort, $order, $queryString){
		WarehouseHelper::createDefaultWarehouseIfNotExists();
		$result=[];
		$query = Warehouse::find();
		if(!empty($queryString)) {
			foreach($queryString as $k => $v) {
				if ($k == 'keyword')
					$query->andWhere(['or',['like','name',$v],['like','comment',$v]]);
				elseif ($k=='is_active' && strtolower($v)!=='all')
					$query->andWhere(['is_active'=>$v]);
				else
					$query->andWhere([$k => $v]);
			}
		}
		
		//读取是否显示海外仓仓库
		$is_show = ConfigHelper::getConfig("is_show_overser_warehouse");
		if(empty($is_show))
			$is_show = 0;
		//不显示海外仓仓库
		if($is_show == 0)
			$query = $query->andWhere(['is_oversea' => '0']);
		
		//不显示已删除的仓库
		$query = $query->andWhere("is_active != 'D' and name!='无'");

		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' => $query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		$query->limit($pagination->limit);
		$query->offset( $pagination->offset );
		$query->orderBy( "$sort $order");
		
		$result['data'] = $query->asArray()->all();

		return $result;
	}	
	
	public static function createDefaultWarehouseIfNotExists(){
		$exists = Warehouse::find()->andWhere(['warehouse_id' => 0])->one();
		if (empty($exists)){
			$defaultWarehouse = new Warehouse();
			$defaultWarehouse->name ="(默认仓库)";
			$defaultWarehouse->is_active = "Y";
			$defaultWarehouse->address_nation = "CN";
			$id = $defaultWarehouse->save(false);
			if (!empty($id)){
				$defaultWarehouse->warehouse_id = 0;
				$defaultWarehouse->save(false);
				//创建默认国家可递送地区
				$queryCountry = SysCountry::find()->asArray()->all();
				foreach ($queryCountry as $country){
					$Countrys[]=$country['country_code'];
				}
				$rtn = self::saveWarehouseReceivingCountry(0,$Countrys,500);
				print_r($rtn);
			}else {
				foreach ($defaultWarehouse->errors as $k => $anError){
					$rtn['message'] .= "E_WHCrt ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To list all Operation Data for this logType and logKey
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param type			LogType
	 * @param page			Page Number to be shown
	 * @param rows			number of rows per page
	 * @param sort          sort by which field
	 * @param order         order by which field
	 * @param queryString   array of criterias
	 +---------------------------------------------------------------------------------------------
	 * @return				To List all warehouse data.
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function listOperationLogData($type,$page, $rows, $sort, $order, $queryString){
		 return OperationLogHelper::loadOperationLog($type, $queryString['log_key']);
	}
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get Warehouse Detai lData
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param id						Warehouse record id
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			array of sql query all with all info
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWarehouseDetailData($id){
		
		$WarehouseModel = Warehouse::findOne($id);
		if ($WarehouseModel<>null) {
			$WarehouseModel->capture_user_id = \Yii::$app->user->id;
		}
		return $WarehouseModel;
//		return Warehouse::model()->findByPk($id);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To insert Warehouse records
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data						data posted with form
	 +---------------------------------------------------------------------------------------------
	 * @return				[success] = true/fasle and [message] if any
	 * 						['warehouse_id'] = warehouse Id created/mofified
	 * This is to insert Warehouse headers and also the details
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveWarehouse($data){
		$rtn['message']="";
		$rtn['success']=true;
		$now_str = GetControlData::getNowDateTime_str();
		$receiving_country = [];
		if(!empty($data['receiving_country']))
			$receiving_country=$data['receiving_country'];
		unset($data['receiving_country']);
		unset($data['receiving_region']);

		//step 1, insert record into Warehouse table if not existing
		//		  or load the warehouse if existing
		$warehouseModel = null;
		if (isset($data['warehouse_id']) and  is_numeric($data['warehouse_id']) ){
			$warehouseModel = Warehouse::findOne(['warehouse_id'=>$data['warehouse_id']]);
		}
		//create one if not existing
		if ($warehouseModel == null){
			$warehouseModel=new Warehouse();
			//check warehouse name is exist or not,if exist then return false
			$warehouseName = $data['name'];
			if(empty($warehouseName)){
				$rtn['success'] = false;
				$rtn['message'] = TranslateHelper::t("仓库名不能为空白！");
				return $rtn;
			}
				
			$warehouseExist = count( Warehouse::findOne(['name'=>$warehouseName]) );
			if ($warehouseExist>0) {
				$rtn['success'] = false;
				$rtn['message'] = TranslateHelper::t("仓库名已存在，请勿重复！");
				return $rtn;
			}
			
			$warehouseModel->create_time = $now_str;
			$created = true;
		}else{
			$created = false;
			//when is changing an existing warehouse, do not change the name of it
			//otherwise, it may mislead users
			unset($data['name']);
		}
		
		if (isset($data['warehouse_id']))
			unset($data['warehouse_id']);
		
		$warehouseModel->attributes=$data; //put the $data field values into aWarehouse
		$warehouseModel->capture_user_id = \Yii::$app->user->id; // \Yii::$app->user->identity->getFullName()
		$warehouseModel->update_time = $now_str;
		
		if(empty($warehouseModel->addi_info)){
			$addi_info = array('address_nation'=>$data['address_nation']);
			$warehouseModel->addi_info = json_encode($addi_info);
		}else{
			$addi_info = json_decode($warehouseModel->addi_info,true);
			$addi_info['address_nation'] =$data['address_nation'];
			$warehouseModel->addi_info = json_encode($addi_info);
		}
		$countryModel = self::getCountryInfoByName($data['address_nation']);
		if($countryModel==null){
			$warehouseModel->address_nation = '';
		}else{
			$warehouseModel->address_nation = $countryModel->country_code;
		}
		
		$transaction = Yii::$app->get('subdb')->beginTransaction();
		if ( $warehouseModel->save() ){//save successfull
			$saveReceivingCountry = self::saveWarehouseReceivingCountry($warehouseModel->warehouse_id,$receiving_country);
			if(!$saveReceivingCountry['success']){
				$transaction->rollBack();
				$rtn['success']=false;
				$rtn['message'] .= $saveReceivingCountry['message'];
			}else{
				$transaction->commit();
				OperationLogHelper::log('Inventory',$warehouseModel->warehouse_id,'userId:'.\Yii::$app->user->id.($created?"创建":"修改")."仓库信息");
			}
		}else{
			$transaction->rollBack();
			$rtn['success']=false;
			foreach ($warehouseModel->errors as $k => $anError){
				$rtn['message'] .= "E_Warehouse_101 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}
		
		//$rtn['warehouse_id'] = $warehouseModel->warehouse_id;
		//SysLogHelper::GlobalLog_Create("inventory",__CLASS__, __FUNCTION__,"","try to modify a warehouse info", "trace");
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To create/modify Warehouse Receiving Country
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data			string|warehouse_id	: 仓库id
	 * 						array|countrys ： receiving_country array(code1,code2,code3.....)
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveWarehouseReceivingCountry($warehouse_id,$countrys=[],$priority=100){
		WarehouseCoverNation::deleteAll(['warehouse_id'=>$warehouse_id]);
		$rtn['message']="";
		$rtn['success']=true;
		if(count($countrys)>0){
			foreach ($countrys as $index=>$code){
				$coverNation = new WarehouseCoverNation();
				$coverNation->nation = $code;
				$coverNation->warehouse_id = $warehouse_id;
				$coverNation->priority = $priority;
				if($coverNation->save()){	
				}else{
					$rtn['success']=false;
					foreach ($coverNation->errors as $k => $anError){
						$rtn['message'] .= "E_saveWarehouseReceivingCountry_101 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
					}
				}
			}
		}
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To find Warehouse Receiving Country
	 * if param:warehouse_id=false,return all country
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data			string|warehouse_id
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getReceivingCountryByWarehouseId($warehouse_id,$isEdit=false){
		$codes = [];//仓库绑定的送达国code数组，用于sql查询条件
		$countrys['receivingCountrys'] =[];//需返回的国家信息数组-仓库已选择；
		$countrys['sysCountrysNotInReceiving'] =[];//需返回的国家信息数组-系统所有支持国家；
		if(is_numeric($warehouse_id)){
			$country_code_arr = WarehouseCoverNation::find()->select('nation')->where(['warehouse_id'=>$warehouse_id])->asArray()->all();
			foreach ($country_code_arr as $row){
				$codes[]=$row['nation'];
			}
			if (count($codes)<=0 and !$isEdit)
				return $countrys;//如果warehouse绑定的国家为空，且不为编辑情景，返回口数组；
		}
		//如果没有传入warehouse_id，则$codes可能用于新建时选择国家，不设限制($codes=[])
		
		$query = SysCountry::find();
		$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
		//获得所有sysCounrty
		foreach ($regions as $region){
			$arr['name']= $region['region'];
			$sql = SysCountry::find()->select([ 'country_code' , "CONCAT( country_zh ,'(', country_en ,')' ) as country_name " ])->where(['region'=>$region['region']]);
			if(count($codes)>0){
				$sql->andwhere(['not in','country_code',$codes]);
			}
			$sqlResult= $sql->orderBy('country_en')->asArray()->all();
			
			if (count($sqlResult)<=0) continue;
			$arr['value']=Helper_Array::toHashmap($sqlResult,'country_code','country_name');
			$countrys['sysCountrysNotInReceiving'][]= $arr;
		}
		//获得所有receivingCountrys
		foreach ($regions as $region){
			$arr['name']= $region['region'];
			if(count($codes)>0){
				$sql = SysCountry::find()->select([ 'country_code' , "CONCAT( country_zh ,'(', country_en ,')' ) as country_name " ])->where(['region'=>$region['region']]);
				$sql->andwhere(['in','country_code',$codes]);
				$sqlResult= $sql->orderBy('country_en')->asArray()->all();
				if (count($sqlResult)<=0) continue;
				$arr['value']=Helper_Array::toHashmap($sqlResult,'country_code','country_name');
				$countrys['receivingCountrys'][]= $arr;
			}else 
				continue;
		}
		
		return $countrys;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取开启的仓库
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param data			string|warehouse_id
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		hqw		2015/12/15		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOpenWarehouseList($type=0){
		$warehouses = Warehouse::find()->select(['warehouse_id','name'])->andWhere(['is_active' => 'Y','is_oversea' => $type])->asArray()->all();
		$result = array();
		
		foreach ($warehouses as $warehousesOne){
			$result[$warehousesOne['warehouse_id']] = array('name'=>$warehousesOne['name'],'is_selected'=>0);
		}
		
		return $result;
	}
	
	/**
	 * 获取仓库类型：0:自定义仓库，1:海外仓
	 */
	public static function getWarehouseType($warehouse_id){
		$result = array('is_oversea'=>0,'oversea_type'=>0);
		
		WarehouseHelper::createDefaultWarehouseIfNotExists();
		$queue=Warehouse::find()->where(['warehouse_id'=>$warehouse_id]);
		$warehouseInfoArr=$queue->select(['warehouse_id','name','is_oversea','oversea_type'])->asArray()->one();
		
		if(count($warehouseInfoArr) >= 0){
			$result['is_oversea'] = $warehouseInfoArr['is_oversea'];
			$result['oversea_type'] = $warehouseInfoArr['oversea_type'];
		}
		
		return $result;
	}
	 
	/**
	 +---------------------------------------------------------------------------------------------
	 *  更新仓库表待发货数量
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function SaveQtyOrdered($warehouseid, $sku, $qty)
	{
	    $rtn['status'] = 1;
	    $rtn['msg'] = '';
		try
		{
		    if( !empty($sku) && $warehouseid >= 0)
		    {
		        //获取主SKU
		        $root_sku = $sku;
		        
		        $transaction = Yii::$app->get('subdb')->beginTransaction();
    			$aProductStockInfo = ProductStock::findOne(['warehouse_id'=>$warehouseid, 'sku'=>$root_sku]);
    			//当不存在则新增
    			if ($aProductStockInfo == null)
    			{
    				$aProductStockInfo = new ProductStock();
    				$aProductStockInfo->warehouse_id =$warehouseid;
    				$aProductStockInfo->sku = (string)$root_sku;
    			}
    	
    			$aProductStockInfo->qty_ordered += $qty;
    	
    			if ( $aProductStockInfo->save())
    			{
    			    //判断是否为捆绑商品，如果是，则也修改对应的配货待发数
    			    $product = Product::findOne(['sku'=>$root_sku]);
    			    if(!empty($product) && $product->type == 'B')
    			    {
    			        //查询子产品信息
    			        $bundlelist = ProductBundleRelationship::find()->select(['assku','qty'])->where(["bdsku"=>$root_sku])->asArray()->all();
    			        if(!empty($bundlelist))
    			        {
    			            foreach ($bundlelist as $index => $bundle)
    			            {
    			                $b_sku = $bundle['assku'];
    			                $ProductStock = ProductStock::findOne(['warehouse_id'=>$warehouseid, 'sku'=>$b_sku]);
    			                //当不存在则新增
    			                if (empty($ProductStock))
    			                {
    			                	$ProductStock = new ProductStock();
    			                	$ProductStock->warehouse_id =$warehouseid;
    			                	$ProductStock->sku = $b_sku;
    			                }
    			                 
    			                $ProductStock->qty_ordered += $qty * $bundle['qty'];
    			                 
    			                if ( !$ProductStock->save())
    			                {
    			                    $rtn['status'] = 0;
    			                    $rtn['msg'] = '保存失败：子产品 '.$b_sku.'，'.$ProductStock->getErrors();
    			                    	
    			                    $transaction->rollBack();
    			                    break;
    			                }
    			            }
    			        }
    			    }
    			    
    			    if($rtn['status'] != 0)
    			        $transaction->commit();
    			}
    			else
    			{
    			    $rtn['status'] = 0;
    			    $rtn['msg'] = '保存失败：'.$aProductStockInfo->getErrors();
    			    
    			    $transaction->rollBack();
    			}
		    }
		}
		catch(\Exception $e)
		{
		    $rtn['status'] = 0;
		    $rtn['msg'] = $e->getMessage().$e->getLine();
		}
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  更新单个sku的待发货数量
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function RefreshOneQtyOrdered($sku)
	{
		try
		{
			if( !empty($sku))
			{
			    //查询对应的别名sku 和 root sku
			    $sku_arr = ProductHelper::getAllAliasRelationBySku( $sku);
			    
			    if(!empty($sku_arr))
			    {
			        $skus = [];
			        foreach ($sku_arr as $key => $val)
			            $skus[] = $key;
			        
			        if(!empty($skus))
			        {
			            $skusStr = '"'.implode('","', $skus).'"';
    			        //查询对应订单明细
    			        $sql = "select sum(oditem.quantity) to_send_qty, od.default_warehouse_id from od_order_v2 od, od_order_item_v2 oditem where
    			                oditem.order_id= od.order_id and order_status>=200 and
    			                order_status<500 and shipping_status<>2 and (order_relation='normal' or order_relation='sm') and sku in (".$skusStr.") group by od.default_warehouse_id";
    			        
    			        $command = Yii::$app->get('subdb')->createCommand($sql);
    			        //$command->bindValue(':skus', $skusStr, \PDO::PARAM_STR);
    			        $rows = $command->queryALL();
    			        
    			        if(!empty($rows))
    			        {
    			            foreach($rows as $row)
    			            {
    			                $warehouseid = $row['default_warehouse_id'];
    			                $qty = $row['to_send_qty'];
    			                
    			                $aProductStockInfo = ProductStock::findOne(['warehouse_id'=>$warehouseid, 'sku'=>$sku]);
    			                 	
    			                //当不存在则新增
    			                if ($aProductStockInfo == null)
    			                {
        			                $aProductStockInfo = new ProductStock();
        			                $aProductStockInfo->warehouse_id =$warehouseid;
        			                $aProductStockInfo->sku = (string)$sku;
    			                }
    			                
    			                $aProductStockInfo->qty_ordered = $qty;
    			                	
    			                if ( $aProductStockInfo->save())
    			                {
    			                    return ['status'=>1, 'msg'=>''];
    			                }
    			                else
    			                {
    			                    return ['status'=>0, 'msg'=>'保存失败'];
    			                }
    			            }
    			        }
    			        else 
    			        {
    			            //清楚所有待发货数量
    			            $command = Yii::$app->get('subdb')->createCommand("update wh_product_stock set qty_ordered=0 where sku='".$sku."'" );
    			            $command->execute();
    			            
    			            return ['status'=>1, 'msg'=>''];
    			        }
			        }
			    }
			}
		}
		catch(\Exception $e)
		{
			return ['status'=>0, 'msg'=>$e->getMessage()];
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  更新指定SKU、仓库的待发货数量
	 +---------------------------------------------------------------------------------------------
	 * @param   $skuArr         array		sku集合
	 * @param   $warehouse_id   int     	仓库id，当缺省时，则更新所有仓库 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/03/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function RefreshSomeQtyOrdered($skuArr, $warehouse_id = null)
	{
	    $ren['status'] = 1;
	    $ren['msg'] = '';
		try{
		    if(empty($skuArr) || !is_array($skuArr)){
		        return $ren;
		    }
		    
		    $order_sku = $skuArr;
		    $ban_info = array();
		    //判断是否存在捆绑子商品
		    $ban_pro = ProductBundleRelationship::find()->where(['assku' => $skuArr])->asArray()->all();
		    if(!empty($ban_pro)){
		    	foreach($ban_pro as $ban){
		    		if(!in_array($ban['bdsku'], $order_sku)){
		    			$order_sku[] = $ban['bdsku'];
		    		}
		    	}
		    }
		    //判断是否存在捆绑商品
		    $ban_pro = ProductBundleRelationship::find()->where(['bdsku' => $order_sku])->asArray()->all();
		    if(!empty($ban_pro)){
		    	foreach($ban_pro as $ban){
		    		$ban_info[$ban['bdsku']][] = $ban;
		    		
		    		if(!in_array($ban['assku'], $order_sku)){
		    			$order_sku[] = $ban['assku'];
		    		}
		    	}
		    }
		    
		    $skusStr = "'".implode("','", $order_sku)."'";
		    
		    //清除所有待发货数量
		    $sql = "update wh_product_stock set qty_ordered=0 where sku in (".$skusStr.") ";
		    if(isset($warehouse_id)){
		    	$sql .= " and warehouse_id=".$warehouse_id;
		    }
		    $command = Yii::$app->get('subdb')->createCommand($sql);
		    $command->execute();
		    
		    //已绑定的店铺账号
		    $stores = '';
		    $puid=\Yii::$app->subdb->getCurrentPuid();
		    $platformAccountInfo = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($puid);
		    foreach ($platformAccountInfo as $p_key=>$p_v){
		    	if(!empty($p_v)){
		    		foreach ($p_v as $s_key=>$s_v){
		    			if($stores == ''){
		    				$stores = '"'.$s_key.'"';
		    			}
		    			else{
		    				$stores .= ',"'.$s_key.'"';
		    			}
		    		}
		    	}
		    }
		    
			//查询对应订单明细，只计算两个月内的订单
			$sql = "select root_sku,sum(oditem.quantity) to_send_qty, od.default_warehouse_id from od_order_v2 od, od_order_item_v2 oditem where
 		                oditem.order_id= od.order_id and ((order_status>=200 and od.order_status<500) or order_status=602) 
  		                and (oditem.manual_status is null or oditem.manual_status!='disable') and (od.order_relation='normal' or od.order_relation='sm' or od.order_relation='ss' or od.order_relation='fs') and (od.order_source!='aliexpress' or od.order_source_status!='RISK_CONTROL') 
						and root_sku in (".$skusStr.")
						and `selleruserid` in (".$stores.") ";
			
			if(isset($warehouse_id)){
				$sql .= " and od.default_warehouse_id=".$warehouse_id;
			}
			$sql .= " group by od.default_warehouse_id, root_sku";
				 
			$command = Yii::$app->get('subdb')->createCommand($sql);
			//$command->bindValue(':skus', $skusStr, \PDO::PARAM_STR);
			//$command->bindValue(':stores', $stores, \PDO::PARAM_STR);
			$rows = $command->queryALL();
			//print_r($rows);die;
			if(!empty($rows)){
				foreach($rows as $row){
					if($row['default_warehouse_id'] > -1){
						//判断是否属于捆绑商品，是则转换为子产品信息
						if(array_key_exists($row['root_sku'], $ban_info)){
							foreach ($ban_info[$row['root_sku']] as $ban){
								$warehouseid = $row['default_warehouse_id'];
								$sku = $ban['assku'];
								$qty = $row['to_send_qty'] * $ban['qty'];
								
								$aProductStockInfo = ProductStock::findOne(['warehouse_id'=>$warehouseid, 'sku'=>$sku]);
								
								//当不存在则新增
								if ($aProductStockInfo == null){
									$aProductStockInfo = new ProductStock();
									$aProductStockInfo->warehouse_id =$warehouseid;
									$aProductStockInfo->sku = (string)$sku;
								}
								
								$aProductStockInfo->qty_ordered += $qty;
								if ( !$aProductStockInfo->save()){
									$ren['status'] = 0;
									$ren['msg'] = '更新失败！';
								}
							}
						}
						else{
							$warehouseid = $row['default_warehouse_id'];
							$sku = $row['root_sku'];
							$qty = $row['to_send_qty'];
								
							$aProductStockInfo = ProductStock::findOne(['warehouse_id'=>$warehouseid, 'sku'=>$sku]);
								
							//当不存在则新增
							if ($aProductStockInfo == null){
								$aProductStockInfo = new ProductStock();
								$aProductStockInfo->warehouse_id =$warehouseid;
								$aProductStockInfo->sku = (string)$sku;
							}
								
							$aProductStockInfo->qty_ordered += $qty;
							if ( !$aProductStockInfo->save()){
								$ren['status'] = 0;
								$ren['msg'] = '更新失败！';
							}
						}
					}
				}
			}
		}
		catch(\Exception $e)
		{
			return ['status'=>0, 'msg'=>$e->getMessage()];
		}
		
		return $ren;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  删除自营仓库
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2017/09/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function DeleteWarehouse($warehouse_id)
	{
		$ret['success'] = true;
		$ret['msg'] = '';
		
		//判断库存信息是否存在
		$stock = ProductStock::findOne(['warehouse_id' => $warehouse_id]);
		if(!empty($stock)){
			return ['success' => false, 'msg' => '此仓库存在库存信息，所以不可删除，请先删除对应的库存信息！'];
		}
		
		//判断是否存在未完成的订单
		$order = OdOrder::find()->where(['default_warehouse_id' => $warehouse_id])->andWhere("((order_status>=200 and order_status<500) or order_status=601 or order_status=602) and order_relation in ('normal' , 'sm', 'ss', 'fs')")->one();
		if(!empty($order)){
			return ['success' => false, 'msg' => '此库存存在未完成订单用到，所以不可删除，请先修改对应的订单仓库信息！'];
		}
		
		$warehouse = Warehouse::findOne(['warehouse_id' => $warehouse_id]);
		if(!empty($warehouse)){
			$name = $warehouse->name;
			$warehouse->is_active = 'D';
			$warehouse->name = $warehouse->name.'--已删除';
			if($warehouse->save()){
				//写入操作日志
				UserHelper::insertUserOperationLog('inventory', '删除仓库, 仓库名: '.$name);
				
				//用到此仓库的已完成、已取消订单
				$order_id_list = array();
				$orders = OdOrder::find()->select("order_id")->where(['default_warehouse_id' => $warehouse_id])->andWhere("(order_status=500 || order_status=600) and order_relation in ('normal' , 'sm', 'ss', 'fs')")->asArray()->all();
				foreach($orders as $order){
					$order_id_list[] = ltrim($order['order_id'], '0');
				}
				$order_str = implode($order_id_list, ',');
				OperationLogHelper::log("warehouse", $warehouse_id, "删除仓库",substr($order_str, 0, 250),\Yii::$app->user->identity->getFullName());
				$puid = \Yii::$app->subdb->getCurrentPuid();
				\Yii::info('delete warehouse puid:'.$puid.' warehouse_id:'.$warehouse_id.' order:'.$order_str, "file");
				
				//查询“已删仓库”的仓库Id
				$warehouse_null = Warehouse::findOne(['is_active' => 'Y', 'name' => '无']);
				if(empty($warehouse_null)){
					$warehouse_null = new Warehouse();
					$warehouse_null->is_active = 'Y';
					$warehouse_null->name = '无';
					if(!$warehouse_null->save()){
						return ['success' => false, 'msg' => '删除仓库失败，转移旧订单仓库事变！'];
					}
				}
				
				//转换此类订单的仓库Id为“已删仓库”Id
				if(!empty($warehouse_null->warehouse_id)){
					OdOrder::UpdateAll(['default_warehouse_id' => $warehouse_null->warehouse_id], ['order_id' => $order_id_list, 'default_warehouse_id' => $warehouse_id]);
				}
				
			}
		}
		
		return $ret;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查询SKU对应的库存信息
	 +---------------------------------------------------------------------------------------------
	 * @param $sku_list        array    sku组合
	 * @param $warehouse_list  array    仓库id组合
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2017/11/02		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GetSkuStock($sku_list, $warehouse_list){
		$stock_list = array();
		try{
			$skus = $sku_list;
			
			//查询是否存在捆绑商品
			$b_skus = array();
			$bund_list = ProductBundleRelationship::find()->select(['bdsku', 'assku', 'qty'])->where(['bdsku' => $skus])->asArray()->all();
			foreach($bund_list as $one){
				$b_skus[strtolower($one['bdsku'])][] = $one;
				$skus[] = $one['assku'];
			}
			//所有sku对应库存
			$sku_stock_list = array();
			$stocks = ProductStock::find()->where(['sku' => $skus, 'warehouse_id' => $warehouse_list])->asArray()->all();
			foreach($stocks as $one){
				$sku_stock_list[strtolower($one['sku'])][$one['warehouse_id']] = ($one['qty_in_stock'] - $one['qty_order_reserved'] < 0) ? 0 : $one['qty_in_stock'] - $one['qty_order_reserved'];
			}
			//sku对应小写
			$sku_list_lower = array();
			foreach($sku_list as $sku){
				$sku_low = strtolower($sku);
				//判断是否捆绑商品库存
				if(array_key_exists($sku_low, $b_skus)){
					foreach($warehouse_list as $warehouse_id){
						$qty = -1;
						foreach($b_skus[$sku_low] as $one){
							$assku = strtolower($one['assku']);
							if($one['qty'] > 0 && !empty($sku_stock_list[$assku][$warehouse_id])){
								$m_qty = intval($sku_stock_list[$assku][$warehouse_id] / $one['qty']);
								if($qty == -1 || $qty > $m_qty){
									$qty = $m_qty;
								}
							}
							else{
								$qty = 0;
								break;
							}
						}
						$stock_list[$warehouse_id][$sku] = ($qty < 0) ? 0 : $qty;
					}
				}
				else if(array_key_exists($sku_low, $sku_stock_list)){
					foreach($sku_stock_list[$sku_low] as $key => $val){
						$stock_list[$key][$sku] = $val;
					}
				}
			}
		}
		catch(\Exception $ex){
			
		}
		
		return $stock_list;
	}
	
}