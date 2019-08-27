<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */

namespace eagle\modules\catalog\helpers;
use eagle\modules\catalog\models\Brand;
use yii;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;
use eagle\models\catalog\Product;

use yii\base\Exception;

class BrandHelper{
	/**
	 +----------------------------------------------------------
	 * 获取brand 数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param na
	 +----------------------------------------------------------
	 * @return				成功则返回品牌数据 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/03/14				初始化
	 +----------------------------------------------------------
	 **/
	static public function ListBrandData(){
		self::createDefaultBrandIfNotExists();
		$result = [];
		$brandlist = Brand::find()->where('brand_id<>0')->asArray()->all();
		foreach($brandlist as $brand){
			$result[$brand['brand_id']] = $brand;
		}
		return $result;
		
	}//end of ListBrandData
	
	/**
	 +----------------------------------------------------------
	 * 根据品牌名字找出品牌编号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $band_name		品牌名字
	 * @param $AutoAdd			自动增加
	 +----------------------------------------------------------
	 * @return		array		
	 * 	boolean			success  执行结果
	 * 	string			message  执行失败的提示信息
	 * 	int				brand_id 品牌编号 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/03/14				初始化
	 +----------------------------------------------------------
	 **/
	static public function getBrandId($band_name , $AutoAdd = FALSE){
		self::createDefaultBrandIfNotExists();
		try {
			$result['success'] = true;
			$result['message'] = '';
			$result['brand_id'] = 0;
			$brand = Brand::findOne(['name'=>$band_name]);
			if (!empty($brand)){
				$result['brand_id'] = (empty($brand->brand_id)?0:$brand->brand_id);
				return $result;
			}else{
				// 自动插入brand
				if ($AutoAdd){
					$brand = new Brand();
					$brand->name = $band_name;
					$brand->create_time = date("Y-m-d H:i:s");
					$brand->capture_user_id = Yii::$app->user->id;
					$insert_rt = $brand->insert();
					

					if ($insert_rt){
						//成功添加后重新获取 brand id
						$tmp_rt = self::getBrandId($band_name);
						if ($tmp_rt['success']){
							//成功直接 返回 , 否则按失败返回
							$result['brand_id'] = $tmp_rt['brand_id'];
							return $result;
						}
					}
					
					$result['success'] = false;
					$result['message'] = '后台添加品牌失败';
					return $result;
				}
			}
		} catch (Exception $e) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
			return $result;
		}
		
	}//end of getBrandId
	
	/**
	 +----------------------------------------------------------
	 * 获取品牌列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				品牌数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function listData($page, $rows, $sort, $order, $queryString)
	{
		self::createDefaultBrandIfNotExists();
		$result=array();
		$result['data']=array();
		$query = Brand::find()->where(['not' ,['brand_id'=>0]]);
		if(!empty($queryString))
		{
			foreach($queryString as $k => $v)
			{
				//$v=trim($v);
				if($v=='') continue;
				if($k=='keyword'){
					$query->andWhere(['or',['like','comment',$v] , ['like','name',$v]]);
				}else{
					$query->andWhere([$k=>$v]);
				}
			}
		}
	
		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' =>$query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;

		$brandRows = $query->orderBy("$sort $order")
						->limit($pagination->limit)
						->offset($pagination->offset)
						->asArray()
						->all();
		foreach ($brandRows as &$row){
			$capture_user_id = $row['capture_user_id'];
			$capture_user_name = UserHelper::getFullNameByUid($capture_user_id);
			if (empty($capture_user_name) && !is_numeric($capture_user_name))
				$capture_user_name="--";
			
			$row['capture_user_name']=$capture_user_name;
			
			$result['data'][]= $row;
		}
	
		return $result;
	}
	/**
	 +----------------------------------------------------------
	 * 获取指定品牌产品数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	查询条件
	 +----------------------------------------------------------
	 * @return				指定品牌产品数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		liang	2014/08/06				初始化
	 +----------------------------------------------------------
	 **/
	public static function productListData($page=1, $pageSie=20, $sort='sku', $order='asc', $BrandId, $keyword='')
	{
		$query=Product::find()->where(['brand_id'=>$BrandId]);
		$keyword = trim($keyword);
		if($keyword!=='')
		{
			$query->andWhere([ 'or', ['like','sku',$keyword],
									 ['like','name',$keyword],
									 ['like','prod_name_ch',$keyword],
									 ['like','prod_name_en',$keyword]
								]);
		}
		
		
		$pagination = new Pagination([
				'defaultPageSize' => $pageSie,
				'totalCount'=> $query->count(),
				'pageSizeLimit'=>[5,200],
				]);
		$result['pagination'] = $pagination;
		
		$result['data'] = $query->orderBy("$sort $order")
					  ->limit($pagination->limit)
			  		  ->offset($pagination->offset)
			  		  ->asArray()
			  		  ->all();

		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存品牌信息
	 +----------------------------------------------------------
	 * @access		static
	 +----------------------------------------------------------
	 * @param 		$data		页面传入的数据
	 +----------------------------------------------------------
	 * @return		array		保存结果
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/06/05		初始化
	 +----------------------------------------------------------
	 **/
	public static function saveBrandInfo($data)
	{
		$result['success']=true;
		$result['message']='';
		$brand_id='';
		if(isset($data['brand_id'])) $brand_id=trim($data['brand_id']);
		if(!is_numeric($brand_id) && $brand_id!==''){
			$result['success']=false;
			$result['message']=TranslateHelper::t("品牌id不正确");
			return $result;
		}
		
		$sameNameBrand = Brand::find()->where(['name'=>$data['name']])->andWhere(['not', ['brand_id' => $brand_id]])->One();
		if($sameNameBrand!==null){
			$result['success']=false;
			$result['message']=TranslateHelper::t("品牌:  ")."<b>".$data['name']."</b>".TranslateHelper::t("  已存在。");
			return $result;
		}

		if($brand_id!==''){
			$model=Brand::findOne($brand_id);
			if($model==null){
				$result['success']=false;
				$result['message']=TranslateHelper::t("品牌id对应的数据已删除或者丢失。");
				return $result;
			}

			$model->attributes=$data;
			$model->brand_id = $brand_id;
			$model->capture_user_id= \Yii::$app->user->id;
			$model->update_time= date('Y-m-d H:i:s', time());
		}else{
			$model=new Brand();
			$model->attributes=$data;
			$model->capture_user_id= \Yii::$app->user->id;
			$model->create_time= date('Y-m-d H:i:s', time());
			$model->update_time= date('Y-m-d H:i:s', time());
		}
		
		if(!$model->save()){
			$result['success']=false;
			$message = '';
			foreach ($model->errors as $k => $anError){
				$message .= "<br>". $k.":".$anError[0];
			}
			$result['message']=TranslateHelper::t("品牌保存失败： Error:saveBrand001。").$message;
			return $result;	
		}
		
		return $result;
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 检查该品牌是否存在
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param attr			属性名称
	 * @param value			属性值
	 +----------------------------------------------------------
	 * @return				是否存在
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function checkBrandExist($attr, $value)
	{
		return Brand::find()->where([$attr=>$value])->all() !=null;
	}
	
	/**
	 +----------------------------------------------------------
	 * 移除产品当前品牌
	 +----------------------------------------------------------
	 * @access		public
	 * @params		$skus		base64 encode后的sku字符串(‘,’分隔)
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/06/05		初始化
	 +----------------------------------------------------------
	 **/
	public static function productRemoveBrand($skus)
	{
		$skuArr = explode(",", $skus);
		foreach ($skuArr as $sku_64){
			$sku_64 = trim($sku_64);
			$sku=base64_decode($sku_64);
			if(!$sku==''){
				$model = Product::findOne($sku);

				$model->brand_id= 0;
				$model->update_time = date('Y-m-d H:i:s', time());
				$model->save(false);
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 移除品牌后，产品也出该品牌
	 +----------------------------------------------------------
	 * @access		public
	 * @params		$ids		已删除的brand的id(‘,’分隔)
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/06/05		初始化
	 +----------------------------------------------------------
	 **/
	public static function productRemoveBrandAfterBrandDel($ids)
	{
		$idArr = explode(",", $ids);
		foreach ($idArr as $id){
			$id = trim($id);
			if(!empty($id)){
				$models = Product::findAll(['brand_id'=>$id]);
				foreach ($models as $prod){
					$prod->brand_id =0;
					$prod->update_time =  date('Y-m-d H:i:s', time());
					$prod->save(false);
				}
			}
		}
	}
	
	protected static function createDefaultBrandIfNotExists(){
		$exists = Brand::find()->where("brand_id=0")->one();
		if (empty($exists)){

			$defaultBrand = new Brand();
			$defaultBrand->name ="";

			$id = $defaultBrand->save(false);
			if (!empty($id)){
				$defaultBrand->brand_id = 0;
				$defaultBrand->save(false);
			}else {
				foreach ($defaultBrand->errors as $k => $anError){
					$rtn['message'] .= "E_BrandCrt ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}
		}
	}
	
}//end of BrandHelper