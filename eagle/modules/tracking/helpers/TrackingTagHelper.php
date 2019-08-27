<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\tracking\helpers;

use eagle\modules\tracking\models\TrackingTags;
use eagle\modules\tracking\models\Tag;
use yii\base\Model;
use eagle\modules\util\helpers\TranslateHelper;

class TrackingTagHelper{
	/* khcomment20150609 
	static private  $TagColorMapping = [
							'red'=>'text-danger' , 
							'blue'=>'text-info',
							'green'=>'text-success',
							'brown'=>'text-warning',
						];
	*/
	static private  $TagColorMapping = [
	'black'=>'egicon-flag-black',
	'blue'=>'egicon-flag-blue',
	'gray'=>'egicon-flag-gray',
	'green'=>'egicon-flag-green',
	'light-blue'=>'egicon-flag-light-blue',
	'navy-blue'=>'egicon-flag-navy-blue',
	'orange'=>'egicon-flag-orange',
	'pink'=>'egicon-flag-pink',
	'purple'=>'egicon-flag-purple',
	'red'=>'egicon-flag-red',
	'yellow'=>'egicon-flag-yellow',
	//'brown'=>'egicon-flag-black'
	];
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 获取 物流 对应 的tag 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$class			string	 	class
	 * 			$tag_name		string		标签名
	 +---------------------------------------------------------------------------------------------
	 * @return str
	 *							Tag Html
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function _generateTagIconHtml( $class , $tag_name , $track_id=''){
	
		if (!empty($tag_name)){
			return '<a title="'.$tag_name.'" style="cursor: pointer;"><span class="'.$class.'" data-track-id="'.$track_id.'" ></span></a>';
			//旧版tag khcomment20150609
			//return '<a title="'.$tag_name.'" class="'.$class.'" style="cursor: pointer;"><span class="glyphicon glyphicon-tag" aria-hidden="true"></span></a>';
		}else{
			return '';
		}
	
	}//end of _generateTagIconHtml
	
	
	
	
	static public function getTagColorMapping($color=''){
		$mapping = self::$TagColorMapping;
		
		if (empty($color)){
			return $mapping;
		}else{
			if (!empty($mapping[$color]))
				return $mapping[$color];
			else 
				return '';
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 获取 物流 对应 的tag 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $tracking_id			string	 	物流号 ID
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *							Tag Html
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateTagIconHtmlByTrackingId($tracking_id){
		$TagData = self::getALlTagDataByTrackId($tracking_id);
		$tmpdata = [];
		$HtmlStr = "";
		if (!empty($TagData['all_tag'])){
			foreach($TagData['all_tag'] as $aTag){
				$tmpdata[$aTag['tag_id']] = $aTag;
			}//end of each tag info 
		}
		
		if (!empty($TagData['all_select_tag_id'])){
			foreach($TagData['all_select_tag_id'] as $tag_id){
				$HtmlStr .= self::_generateTagIconHtml($tmpdata[$tag_id]['classname'], $tmpdata[$tag_id]['tag_name'] , $tracking_id);
				
			}//end of each tracking tag
		}else{
			$tmpTag = TranslateHelper::t('添加标签');
			$HtmlStr .= self::_generateTagIconHtml('egicon-flag-gray',$tmpTag , $tracking_id);
		}
		
		return $HtmlStr;
	}//end of generateTagIconHtmlByTrackingId
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 参数获取   指定 的物流标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $TagIdList			array 	tag_id 的数据集
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 						Tag model 的数据 结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getTagByTagID($TagIdList = [] , $order='color' ,$sort='desc'){
		$condition = [];
		if (!empty($TagIdList)){
			$condition = ['tag_id'=>$TagIdList];
		}
		$TagList = Tag::find()->where($condition)
					->orderBy(" $order $sort")
					->asArray()->all();
		$mapping = self::$TagColorMapping;
		foreach($TagList as &$aTag){
			if (array_key_exists($aTag['color'], $mapping)){
				$aTag['classname'] = $mapping[$aTag['color']];
			}
		}
		return $TagList;
	}//end of getTrackingTagByTrackingTagID
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据获取   指定 的物流标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $track_no			string	 	物流号
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *					TrackingTags model 的数据结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getTrackingTagsByTrackId($tracking_id){
		$TrackingTagsList = TrackingTags::find()->where(['tracking_id'=>$tracking_id])->asArray()->all();
		return $TrackingTagsList;
	}//end of getTrackingTagsByTrackId
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 获取 物流 对应 的tag 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $track_id			string	 	物流号 ID
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *							Tag model 的数据结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getALlTagDataByTrackId($track_id){
		$TrackingTags = self::getTrackingTagsByTrackId($track_id);
		$TagIdList=[];
		foreach($TrackingTags as $oneTrackingTag){
			$TagIdList [] = $oneTrackingTag['tag_id'];
		}
		
		$AllTagData = self::getTagByTagID();
		$TagData['all_tag'] = $AllTagData;
		$TagData['all_select_tag_id'] = $TagIdList;
		return $TagData;
	}// end of getALlTagDataByTrackId
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存tag 的数据 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $TagList			array('tag_id'=> ,   		空为新增
	 * 									'tag_name'=> , 		必填
	 * 									'color'=>)			必填
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @return  array					关于执行结果的报告
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveTagList(&$TagList){
		$result = [];
		$_mapping = self::$TagColorMapping;
		$fieldList = array_flip($_mapping);
		//生成name的数组
		foreach($TagList as &$oneTag){
			
			
			if (!empty($oneTag['tag_id'])){
				$model = Tag::findOne(['tag_id'=>$oneTag['tag_id']]);
			}
			
			if (empty($model)){
				if (!empty($oneTag['tag_name'])){
					$model = Tag::findOne(['tag_name'=>$oneTag['tag_name']]);
				}
			}
			
			if (empty($model)) $model = new Tag();
			
			if (!empty($oneTag['tag_name'])){
				$model->tag_name = $oneTag['tag_name'];
				$TagNameList [] = $oneTag['tag_name'];
			}
			
			
			if (!empty($oneTag['color']))
				$model->color = $oneTag['color'];
			else if (!empty($oneTag['classname'])){
				if (array_key_exists($oneTag['classname'], $fieldList)){
					$oneTag['color'] = $fieldList[$oneTag['classname']];
				}
			}
			
			
			
			$result[$oneTag['tag_name']] = $model->save();
			
			if (empty($oneTag['tag_id'])) $oneTag['tag_id'] = $model->tag_id;
			unset($model);
		}
		
		//删除 不存在的 数组
		if (!empty($TagNameList))
			$result['delete'] = Tag::deleteAll([ 'not in', 'tag_name', $TagNameList]);
		return $result;
	}//end of saveTagList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存tracking tag 的数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$TrackingId			string 				物流ID
	 * 				$TagIdList			array				Tag id 的数组
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *							Tag model 的数据结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveTrackingTags($TrackingId , $TagIdList){
		TrackingTags::deleteAll(['tracking_id'=>$TrackingId]);
		$model = new TrackingTags();
		foreach($TagIdList as $TagId){
			$_model = clone $model;
			$_model->tracking_id = $TrackingId;
			$_model->tag_id = $TagId;
			$_model->save();
			unset($_model);
		}
	}//end of saveTrackingTag
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装保存tag 档案数据  和  tracking tag 的数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$tracking_id			string			tracking no 对应 的ID
	 *				$TrackingTagIdList		array 			Tracking tag  的业务数据  (tag id 的数组 )
	 *				$isEditTag				boolen 			是否修改tag 档案
	 *				$TagData				array			tag 档案 的数据集
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *							Tag model 的数据结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveTagAndTrackingTags( $tracking_id, $TrackingTagIdList , $isEditTag = false ,$TagData =[] ){
		//save Tag record
		if ($isEditTag){
			self::saveTagList($TagData);
		}
		
		$Mapping = [];
		//generate mapping between Tag id and tag name  
		foreach($TagData as $onetag){
			$Mapping[$onetag['tag_name']] = $onetag['tag_id'];
		}
		
		//checked tracking tags data 
		foreach($TrackingTagIdList as &$oneTrackingTag){
			if (array_key_exists($oneTrackingTag, $Mapping))
				$oneTrackingTag = $Mapping[$oneTrackingTag];
		}
		
		//save tracking tags record
		self::saveTrackingTags($tracking_id, $TrackingTagIdList);
		$result = ['success'=>true,'message'=>'保存成功!请手动刷新看结果'];
		return $result;
	}//end of saveTrackingTagsByData
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 新版 保存tag 档案数据  和  tracking tag 的数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$tracking_id			string			tracking no 对应 的ID
	 *				$tag_name				string 			Tracking tag 名称
	 *				$operation				string 			add/del
	 *				$color					string			tag 使用哪种颜色
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *							Tag model 的数据结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveOneTrackingTag($tracking_id , $tag_name , $operation ,$color ){
		//检查标签  是否新标签
		$tagModel = Tag::find()->andWhere(['tag_name'=>$tag_name])->one();
		$rt = true;
		if (strtolower($operation) == "add"){
			
			
			if (empty($tagModel) ){
				//新标签并保存
				$tagModel = new Tag();
				$tagModel->tag_name = $tag_name;
				$tagModel->color = $color;
				$rt = $tagModel->save();
			}
			
			if (! $rt) return ['success'=>false , 'message'=>'标签档案保存失败!'];
			//添加标签  
			$TrackingTagsModel = TrackingTags::find()->andWhere(['tag_id'=>$tagModel->tag_id , 'tracking_id'=>$tracking_id])->one();
			
			if (empty($TrackingTagsModel)){
				$TrackingTagsModel = new TrackingTags();
				$TrackingTagsModel->tag_id = $tagModel->tag_id;
				$TrackingTagsModel->tracking_id = $tracking_id;
				$rt = $TrackingTagsModel->save();
				
				if (! $rt) return ['success'=>false , 'message'=>'标签保存失败!'];
			}
		}else{
			//只有打到对应 的标签 才进行删除 
			if (! empty($tagModel) ){
				//删除标签
				$rt = TrackingTags::deleteAll(['tracking_id'=>$tracking_id , 'tag_id'=>$tagModel->tag_id ]);
				
				if (! $rt) return ['success'=>false , 'message'=>'标签删除失败!'];
			}
		}
		
		return ['success'=>true , 'message'=>'操作成功!'];
		
	}//end of saveOneTrackingTag
	
	
	
	
}//end of class
?>