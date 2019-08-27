<?php
namespace eagle\modules\listing\helpers;
use eagle\modules\listing\models\EbayStorecategory;
use common\helpers\Helper_Array;
class StorecategoryHelper {

	/*
	 * 保存ebay自定义店铺类目
	 * 
	 */
	static function saveStoreCategory($uid,$selleruserid,$customCategoryArray,$parentid=0){
		if(is_array($customCategoryArray)&&count($customCategoryArray)){
			if(isset($customCategoryArray['CategoryID'])){
				$customCategoryArray=array(0=>$customCategoryArray);
			}
			foreach($customCategoryArray as $customCategory){
				$ESCC=EbayStorecategory::findOne(['uid'=>$uid,'selleruserid'=>$selleruserid,'categoryid'=>$customCategory['CategoryID']]);
				if(empty($ESCC)){
					$ESCC= new EbayStorecategory();
					$ESCC->uid=$uid;
					$ESCC->selleruserid=$selleruserid;
				}
				$ESCC->categoryid=$customCategory['CategoryID'];
				$ESCC->category_name=$customCategory['Name'];
				$ESCC->category_order=@$customCategory['Order'];
				$ESCC->category_parentid=$parentid;
				$ESCC->save();
				if(isset($customCategory['ChildCategory'])){
					self::saveStoreCategory($uid,$selleruserid,$customCategory['ChildCategory'],$customCategory['CategoryID']);
				}
			}
		}
	}
	/***
	 * 清空 已经 不再使用的 用户自定义ebay类目.
	*/
	static function clearStoreCategory($uid,$selleruserid,$customCategoryArray){
		$list=array();
		$categoryids=array();
		self::storeCategoryTree2List($list,$customCategoryArray);
		if(count($list)){
			$categoryids = Helper_Array::getCols($list,'CategoryID');
			$categoryids=array_filter($categoryids);
			$search='uid = '.$uid.' AND selleruserid = "'.$selleruserid.'" AND categoryid not in ('.implode(',', $categoryids).')';
			EbayStorecategory::deleteAll($search);
		}else{
			EbayStorecategory::deleteAll(['uid'=>$uid,'selleruserid'=>$selleruserid]);
		}
	}
	
	/***
	 * 将 从Ebay store类目  读取来 树形,排成单行 列表.
	*/
	static function storeCategoryTree2List(&$list,$customCategoryArray){
		if(is_array($customCategoryArray)&&count($customCategoryArray)){
			if(isset($customCategoryArray['CategoryID'])){
				$list[$customCategoryArray['CategoryID']]=$customCategoryArray;
				if(isset($customCategoryArray['ChildCategory'])){
					self::storeCategoryTree2List($list,$customCategoryArray['ChildCategory']);
				}
			}else{
				foreach($customCategoryArray as $category){
					self::storeCategoryTree2List($list,$category);
				}
			}
		}
	}

}
