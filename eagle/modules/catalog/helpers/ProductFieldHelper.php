<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\catalog\helpers;
use eagle\modules\catalog\models\ProductField;
use eagle\modules\catalog\models\ProductFieldValue;

use yii\db\Query;
use eagle\models\catalog\Product;
use Zend\Paginator\ScrollingStyle\All;
use eagle\modules\util\helpers\TranslateHelper;

class ProductFieldHelper{
	
	/**
	 +----------------------------------------------------------
	 * 获取产品属性列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryParams	其他条件:'name'->colunm,'condition'->conditionType,value->conditionValue
	 +----------------------------------------------------------
	 * @return				产品属性数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl	2015/05/6				初始化
	 +----------------------------------------------------------
	**/
	public static function listData($page, $rows, $sort, $order, $queryParams)
	{
		$query = ProductField::find()->where("id <> 0 ");
		if(!empty($queryParams))
		{
			foreach($queryParams as $index => $param)
			{
				if($param['condition']=='eq'){
					$query->andWhere([$param['name']=>$param['value']]);
				}
				else{
					$query->andWhere([$param['condition'],$param['name'],$param['value']]);
				}
				
			}
		}

		$query->limit($rows);
		$query->offset(($page-1) * $rows);

		$query->orderBy("$sort $order");//排序条件

		$rtn = $query->asArray()
			  ->all();
		//记录总行数
		$result['total'] = count($query);
		$result['rows'] = $rtn;
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取指定属性产品数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	查询条件
	 +----------------------------------------------------------
	 * @return				指定属性产品数据列表
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/05/06		初始化
	 +----------------------------------------------------------
	**/	
	public static function productListData($page, $rows, $sort, $order, $fieldId, $queryString)
	{	
		$fieldModel = ProductField::findOne($fieldId);
		$fieldName = $fieldModel->field_name;
		
		$prodModels = Product::find()->where(['like','other_attributes',"%$fieldName".":%"]);
		if(!empty($queryString)) //搜索条件
		{
			foreach($queryString as $k => $v)
			{
				if ($k == 'sku' or $k=='name')
					$prodModels->andWhere(['like',$k,$v]);
				else
					$prodModels->andWhere([$k=>$v]);
			}
		}
		$prodModels->limit( $rows );
		$prodModels->offset( ($page-1) * $rows );
		$prodModels->orderBy( "$sort $order" );//排序条件
		$prodModels->asArray()
				   ->all();
		$result['total'] = count($prodModels);
		$result['rows'] = $prodModels;
		return $result;
	}	
	
	/**
	 +----------------------------------------------------------
	 * 当产品信息保存时，更新属性表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param fieldStr		产品保存时记录的属性字符串
	 +----------------------------------------------------------
	 * @return				true
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/06		初始化
	 +----------------------------------------------------------
	**/
	public static function updateField($fieldStr) 
	{
		$field = [];
		$attr_pair  = explode(";", $fieldStr);
		foreach ($attr_pair as $i=>$pair){
			$a_attr = explode(":", $pair);
			if(!empty($a_attr[0])){
				if(isset($a_attr[1]))
					$field[$a_attr[0]] = $a_attr[1];
			}
		}
		foreach ($field as $name => $value) {
			//update field table
			$fieldModel = ProductField::findOne(['field_name'=>$name]);
			if ($fieldModel==null) {
				$fieldModel = new ProductField();
				$fieldModel->field_name = $name;
				$fieldModel->use_freq = 1;
			}else {
				//$fieldModel->use_freq = $fieldModel->use_freq+1;//db update 'use_freq'时会报错，暂时屏蔽
			}
			$fieldModel->save(false);
			//update field_value table
			$fieldId = $fieldModel->id;
			$fieldValueModel = ProductFieldValue::findOne(['field_id'=>$fieldId,'value'=>$value]);
			if ($fieldValueModel==null) {
				$fieldValueModel = new ProductFieldValue();
				$fieldValueModel->field_id = $fieldId;
				$fieldValueModel->value = $value;
				$fieldValueModel->use_freq = 1;
			}else {
				//$fieldValueModel->use_freq = $fieldValueModel->use_freq+1;//db update 'use_freq'时会报错，暂时屏蔽
			}
			$fieldValueModel->save(false);
		}

		return true;
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据属性名字找出属性编号
	 * 如果没有该属性，则新建并返回编号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param 		$field_name		属性名字
	 +----------------------------------------------------------
	 * @return		tag_id 			标签编号
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/06		初始化
	 +----------------------------------------------------------
	 **/
	static public function getProductFieldIdByName($field_name){
		$field = ProductField::findOne(['field_name'=>$field_name]);
		if ($field==null) {
			$field = new ProductField();
			$field->field_name = $field_name;
			$field->use_freq = 0;
			$field->save(false);
		}
		$field_id = $field->id;
		
		return $field_id;
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据商品的sku 找出属性集 
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $sku		商品的sku
	 +----------------------------------------------------------
	 * @return		array
	 * 	boolean			success  执行结果
	 * 	string			message  执行失败的提示信息
	 * 	array			fields   属性集 
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/06		初始化
	 +----------------------------------------------------------
	 **/
	static public function getOneProductFields($sku){
		$result['success'] = true;
		$result['message'] = '';
		$result['fields'] = [];
		
		$prod = Product::findOne($sku);
		if ($prod==null){
			$result['success'] = false;
			$result['message']=TranslateHelper::t('商品').$sku.TranslateHelper::t('不存在！');
			return $result;
		}
		if ($prod->other_attributes ==''){
			$result['message']=TranslateHelper::t('商品').$sku.TranslateHelper::t('没有额外属性');
			return $result;
		}else{
			$fieldStr = $prod->other_attributes;
			$attr_pair  = explode(";", $fieldStr);
			foreach ($attr_pair as $i=>$pair){
				$a_attr = explode(":", $pair);
				if(!empty($a_attr[0])){
					if(isset($a_attr[1]))
						$result['fields'][$a_attr[0]] = $a_attr[1];
				}
			}
		}
		return $result;
	}
	
	/**
	 * 属性  名->值  字符串去除重复。如果有属性名重复，值为最后一次出现的值
	 * @param  $fieldStr
	 * @return string
	 */
	static public function uniqueProductFieldStr($fieldStr){
		$fields=[];
		$attr_pair  = explode(";", $fieldStr);
		foreach ($attr_pair as $i=>$pair){
			$a_attr = explode(":", $pair);
			if(!empty($a_attr[0])){
				if(isset($a_attr[1]))
					$fields[$a_attr[0]] = $a_attr[1];
			}
		}
		$uniqueStr = '';
		foreach ($fields as $k=>$v){
			if($uniqueStr=='')
				$uniqueStr.=$k.":".$v;
			else
				$uniqueStr.=";".$k.":".$v;
		}
		return $uniqueStr;
	}
	
	/**
	 * 获取已有的属性名称
	 * @param  $sort	输出的排序条件
	 * @param  $order	排序 DESC;ASC 
	 * @param  $rows	返回数目限制
	 * @return array	array(name1,name2,name3.....)
	 * * log			name		date			note
	 * @ author			lzhl		2015/05/14		初始化
	 */
	static public function getFieldNames($sort=false,$order=false,$rows=false){
		$names=[];
		$query=ProductField::find()->select(['field_name','id']);
		if( ($sort && $sort!=='') && ($order && $order!=='') ){
			$query->orderBy("$sort $order");
		}
		if( $rows && is_numeric($rows) ){
			$query->limit($rows);
		}
		$rtn=$query->asArray()->all();
		foreach ($rtn as $aRow){
			$names[$aRow['id']]=$aRow['field_name'];
		}

		return $names;
	}
}