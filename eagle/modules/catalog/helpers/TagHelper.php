<?php
namespace eagle\modules\catalog\helpers;
use eagle\modules\catalog\models\Tag;

use yii\db\Query;
use yii;
use eagle\modules\catalog\models\ProductTags;
use yii\data\Pagination;
use eagle\models\catalog\Product;
use eagle\modules\util\helpers\TranslateHelper;

use yii\base\Exception;

class TagHelper{
	
	/**
	 +----------------------------------------------------------
	 * 获取标签列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
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
	public static function listData($page, $rows, $sort, $order, $queryString)
	{
		$query=Tag::find()->where(['not',['tag_id'=>0]]);
		if(!empty($queryString))
		{
			foreach($queryString as $k => $v)
			{
				$v=trim($v);
				if($v=='') continue;
				if($k=='keyword'){
					$query->andWhere(['like','tag_name',$v]);
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
		
		$result['data'] = $query->orderBy("$sort $order")
						 		->limit($pagination->limit)
						 		->offset($pagination->offset)
						 		->asArray()
								->all();

		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取指定标签产品数据
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
	 * log			name		date				note
	 * @author		liang		2014/08/12			初始化
	 +----------------------------------------------------------
	**/	
	public static function productListData($page, $rows, $sort, $order, $tagid, $queryString)
	{	
		/*
		$query = ProductTags::find()->where(['tag_id'=>$tagid]);
		
		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' =>$query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		$data = $query->orderBy("$sort $order")
					  ->limit($pagination->limit)
					  ->offset($pagination->offset)
					  ->asArray()
					  ->all();

		$skuArray = array();
		if(!empty($data)) {
			for ( $i = 0; $i < count($data); $i++){		
				$skuArray[] = $data[$i]['sku'];
			}
		}
		*/
		$sql="select t.*,p.sku,p.name,p.photo_primary,p.type,p.status,p.product_id 
				from pd_product_tags t , pd_product p 
				where t.tag_id=:tag_id and t.sku=p.sku ";
		$bindParmValues = array();
		$bindParmValues["tag_id"] = $tagid;
		if(!empty($queryString)){
			foreach($queryString as $k => $v)
			{
				$v=trim($v);
				if($v=='') continue;
				
				if($k=='keyword'){
					$sql.=" and ( p.sku like :keyword or p.name like :keyword or 
							p.prod_name_ch like :keyword or p.prod_name_en like :keyword) ";
					$keyword = "%".$v."%";
					$bindParmValues["keyword"] = $keyword;
				}else{
					$bindParmKey = ":".$k;
					$sql.=" and  $k = $bindParmKey ";
					$bindParmValues[$k] = $v;
				}
			}
		}
		$command_all = Yii::$app->get('subdb')->createCommand($sql);
		//bind the parameter values
		foreach ($bindParmValues as $k=>$v){
			$bindTarget = trim(":".$k);
			if($k=='tag_id')
				$command_all->bindValue($bindTarget, ($v), \PDO::PARAM_INT);
			else
				$command_all->bindValue($bindTarget, ($v), \PDO::PARAM_STR);
		}
		
		$pagination = new Pagination([
				'pageSize' => $rows,
				'totalCount' =>count($command_all->queryAll()),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$result['pagination'] = $pagination;
		
		$sql.="order by p."."$sort $order limit $pagination->offset , $pagination->limit";
		$command = Yii::$app->get('subdb')->createCommand($sql);
		//bind the parameter values
		foreach ($bindParmValues as $k=>$v){
			$bindTarget = trim(":".$k);
			if($k=='tag_id')
				$command->bindValue($bindTarget, ($v), \PDO::PARAM_INT);
			else
				$command->bindValue($bindTarget, ($v), \PDO::PARAM_STR);
		}
		$data = $command->queryAll();
		/*
		$skuArray = array();
		if(!empty($data)) {
			for ( $i = 0; $i < count($data); $i++){
				$skuArray[] = $data[$i]['sku'];
			}
		}
		
		$command->bindValue(":purchase_arrival_id", $id, \PDO::PARAM_STR);
		
		$result['rows'] = $command->queryAll();
		
		$pro = new CDbCriteria();
		$pro->addInCondition('sku', $skuArray);
		if(!empty($queryString)) //搜索条件
		{
			foreach($queryString as $k => $v)
			{
				if ($k == 'sku_CNname'){
					$pro->addCondition("sku like '%$v%' or prod_name_ch like '%$v%'");}
				else{
					$pro->addCondition("$k = '$v'");}	
			}
		}
		$proInfo =  Product::model()->findAll($pro);
		*/
		$result['data'] = $data;
		return $result;
	}	
	
	/**
	 +----------------------------------------------------------
	 * 更新商品的标签
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sku			需要更新的商品SKU
	 * @param tags			标签列表
	 +----------------------------------------------------------
	 * @return				商品所有的标签条数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		ouss	2014/02/27				初始化
	 +----------------------------------------------------------
	**/
	public static function updateTag($sku, $tags) 
	{
		if (!isset($tags)){
			$tags = array();
		}
		$tagIdArray = array();
		foreach ($tags as $tag) {
			
			$modelTag = Tag::findOne( array('tag_name' => $tag));
			if ($modelTag != null){
				$tagIdArray[$modelTag->tag_id] = $tag;
			}else {
				$modelTag = new Tag();
				$modelTag->tag_name = $tag;
				if ($modelTag->save()) {
					$tagIdArray[$modelTag->tag_id] = $tag;
				}
			}
		}
		if(count($tagIdArray) > 0) {
			
			$productTags = ProductTags::findAll(array('sku' => $sku));
			foreach ($productTags as $pTag) {
				if(!isset($tagIdArray[$pTag->tag_id])){
					$pTag->delete();
				}else {
					unset($tagIdArray[$pTag->tag_id]);
				}
			}
			
			foreach ($tagIdArray as $tagId => $name) {
				$productTag = new ProductTags();
				$productTag->sku = $sku;
				$productTag->tag_id = $tagId;
				$productTag->save();
			}
		}	
		$result = ProductTags::findAll(['sku' => $sku]);
		
		return count($result)  > 0;
	}
	

	/**
	 +----------------------------------------------------------
	 * 新增商品标签
	 * 如果原有标签不在标签列表，不作delete处理 [区别于updateTag($sku, $tags)]
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sku			需要更新的商品SKU
	 * @param tags			标签列表
	 +----------------------------------------------------------
	 * @return				商品所有的标签条数
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/05/04		初始化
	 +----------------------------------------------------------
	 **/
	public static function productAddTag($sku, $tags)
	{
		if (!isset($tags)){
			$tags = array();
		}
		$tagIdArray = array();
		foreach ($tags as $tag) {
				
			$modelTag = Tag::findOne( array('tag_name' => $tag));
			if ($modelTag != null){
				$tagIdArray[$modelTag->tag_id] = $tag;
			}else {
				$modelTag = new Tag();
				$modelTag->tag_name = $tag;
				if ($modelTag->save()) {
					$tagIdArray[$modelTag->tag_id] = $tag;
				}
			}
		}
		if(count($tagIdArray) > 0) {
			$productTags = ProductTags::findAll(array('sku' => $sku));
			foreach ($productTags as $pTag) {
				if(isset($tagIdArray[$pTag->tag_id]))
				{
					unset($tagIdArray[$pTag->tag_id]);
				}
			}
				
			foreach ($tagIdArray as $tagId => $name) {
				$productTag = new ProductTags();
				$productTag->sku = $sku;
				$productTag->tag_id = $tagId;
				$productTag->save();
			}
		}
		$result = ProductTags::findAll(['sku' => $sku]);
	
		return count($result)  > 0;
	}
		
	
	/**
	 +----------------------------------------------------------
	 * 根据标签名字找出标签编号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $tag_name		标签名字
	 * @param $AutoAdd		自动增加
	 +----------------------------------------------------------
	 * @return		array
	 * 	boolean			success  执行结果
	 * 	string			message  执行失败的提示信息
	 * 	int				tag_id 标签编号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/03/16				初始化
	 +----------------------------------------------------------
	 **/
	static public function getTagId($tag_name , $AutoAdd = FALSE){
		try {
			$result['success'] = true;
			$result['message'] = '';
			$result['tag_id'] = 0;
			$tag = Tag::findOne(['name'=>$tag_name]);
			if (!empty($tag)){
				$result['tag_id'] = (empty($tag->tag_id)?"":$tag->tag_id);
				return $result;
			}else{
				// 自动插入Tag
				if ($AutoAdd){
					$tag = new Tag();
					$tag->name = $tag_name;
					$insert_rt = $tag->insert();
	
	
					if ($insert_rt){
						//成功添加后重新获取 Tag id
						$tmp_rt = self::getTagId($tag_name);
						if ($tmp_rt['success']){
							//成功直接 返回 , 否则按失败返回
							$result['tag_id'] = $tmp_rt['tag_id'];
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
	
	}//end of getTagId
	
	/**
	 +----------------------------------------------------------
	 * 根据商品的sku 找出标签集 
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $sku		商品的sku
	 +----------------------------------------------------------
	 * @return		array
	 * 	boolean			success  执行结果
	 * 	string			message  执行失败的提示信息
	 * 	array			tags     标签集 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/29				初始化
	 +----------------------------------------------------------
	 **/
	static public function getOneProductTags($sku){
		try {
			$result['success'] = true;
			$result['message'] = '';
			$result['tags'] = Tag::find()
			->andWhere(['in' , 'tag_id',(new Query())->select(['tag_id'])->from('pd_product_tags')->where(['sku'=>$sku])])
			->asArray()
			->All();
		} catch (Exception $e) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
			$result['tags'] = [];
		}
		return $result;
	}//end of getOneProductTags
	

	/**
	 +----------------------------------------------------------
	 * 保存标签信息
	 +----------------------------------------------------------
	 * @access		static
	 +----------------------------------------------------------
	 * @param 		$data		页面传入的数据
	 +----------------------------------------------------------
	 * @return		array		保存结果
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/06/12		初始化
	 +----------------------------------------------------------
	 **/
	public static function saveTagInfo($data)
	{
		$result['success']=true;
		$result['message']='';
		$tag_id='';
		if(!empty($data['tag_id'])) $tag_id=trim($data['tag_id']);
		if(!is_numeric($tag_id) && $tag_id!==''){
			$result['success']=false;
			$result['message']=TranslateHelper::t("标签id不正确");
			return $result;
		}
	
		$sameNameTag = Tag::find()->where(['tag_name'=>$data['tag_name']])->andWhere(['not', ['tag_id' => $tag_id]])->One();
		if($sameNameTag!==null){
			$result['success']=false;
			$result['message']=TranslateHelper::t("标签:  ")."<b>".$data['tag_name']."</b>".TranslateHelper::t("  已存在。");
			return $result;
		}
	
		if($tag_id!==''){
			$model=Tag::findOne($tag_id);
			if($model==null){
				$result['success']=false;
				$result['message']=TranslateHelper::t("标签id对应的数据已删除或者丢失。");
				return $result;
			}
	
			$model->tag_name=$data['tag_name'];
		}else{
			$model=new Tag();
			$model->attributes=$data;
		}
	
		if(!$model->save()){
			$result['success']=false;
			$message = '';
			foreach ($model->errors as $k => $anError){
				$message .= "<br>". $k.":".$anError[0];
			}
			$result['message']=TranslateHelper::t("标签保存失败： Error:saveTag001。").$message;
			return $result;
		}
	
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 移除产品指定 tag
	 +----------------------------------------------------------
	 * @access		public
	 * @params		$skus		base64 encode后的sku字符串(‘,’分隔)
	 * 				$tag_id		需要删除的标签id
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/06/12		初始化
	 +----------------------------------------------------------
	 **/
	public static function productRemoveTag($skus,$tag_id)
	{
		$result['success']=true;
		$result['message']='';
		foreach ($skus as $sku){
			if(!$sku==''){
				//删除产品该tag
				try{
					ProductTags::deleteAll('sku=:sku and tag_id=:tag_id',[':sku'=>$sku,':tag_id'=>$tag_id]);
				}catch (Exception $e) {
					$result['success'] = false;
					$result['message'] = $e->getMessage();
				}
				//删除后检测产品是否还有其他tag，无则update产品is_has_tag属性
				$pt = ProductTags::find()->where(['sku'=>$sku])->asArray()->all();
				if(empty($pt)){
					try{
						Product::updateAll( ['is_has_tag'=>'N'],'sku=:sku',[':sku'=>$sku] );
					}catch (Exception $e) {
						$result['success'] = false;
						$result['message'] = $e->getMessage();
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 移除标签后，产品删除该标签
	 +----------------------------------------------------------
	 * @access		public
	 * @params		$ids		已删除的tag的id(‘,’分隔)
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		liang		2015/06/12		初始化
	 +----------------------------------------------------------
	 **/
	public static function productRemoveTagAfterTagDel($ids)
	{
		$idArr = explode(",", $ids);
		foreach ($idArr as $id){
			$id = trim($id);
			if(!empty($id)){
				$skuList = array();
				$models = ProductTags::findAll(['tag_id'=>$id]);
				foreach ($models as $prodTag){
					$skuList[]=$prodTag->sku;
				}
				ProductTags::deleteAll('tag_id=:tag_id',[':tag_id'=>$id]);

				//删除后检测产品是否还有其他tag，无则update产品is_has_tag属性
				foreach ($skuList as $sku){
					$pt = ProductTags::find()->where(['sku'=>$sku])->asArray()->all();
					if(empty($pt)){
						Product::updateAll( ['is_has_tag'=>'N'],'sku=:sku',[':sku'=>$sku] );
					}
				}
				
			}
		}
	}

	
}//end of TagHelper