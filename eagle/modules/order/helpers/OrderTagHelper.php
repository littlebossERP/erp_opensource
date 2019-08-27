<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;

use eagle\modules\order\models\LtOrderTags;
use eagle\modules\order\models\Ordertag;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OrderSystagsMapping;
use eagle\modules\order\models\OdOrder;
use eagle\modules\permission\helpers\UserHelper;

class OrderTagHelper{
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
	 * 订单模块 系统标签
	 */
	static public $OrderSysTagMapping = [
		'pay_memo'=>'有付款备注',
		'order_memo'=>'有订单备注',
		'new_msg_tag'=>'有未读新留言',
		'sys_unshipped_tag'=>'已虚拟发货',
		'favourable_tag'=>'已给买家好评',
		//'refund_tag'=>'已退款',
		//'return_tag'=>'已退货',
		//'platform_shipped_tag'=>'已标记发货',
		//'skip_merge'=>'不合并发货',
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
	static private function _generateTagIconHtml( $class , $tag_name , $orderid=''){
	
		if (!empty($tag_name)){
			return '<a title="'.$tag_name.'" style="cursor: pointer;"><span class="'.$class.'" data-order-id="'.$orderid.'" ></span></a>';
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
	static public function generateTagIconHtmlByOrderId($orderid){
		if ($orderid instanceof OdOrder){
			$tmpOrderId = $orderid->order_id;
			unset($orderid);
			$orderid = $tmpOrderId;
		}
		
		$orderid = (int)$orderid;
		$TagData = self::getALlTagDataByOrderId($orderid);
		
		$tmpdata = [];
		$HtmlStr = "";
		if (!empty($TagData['all_tag'])){
			foreach($TagData['all_tag'] as $aTag){
				$tmpdata[$aTag['tag_id']] = $aTag;
			}//end of each tag info 
		}
		
		
		
		if (!empty($TagData['all_select_tag_id'])){
			foreach($TagData['all_select_tag_id'] as $tag_id){
				$HtmlStr .= self::_generateTagIconHtml($tmpdata[$tag_id]['classname'], $tmpdata[$tag_id]['tag_name'] , $orderid);
				
			}//end of each tracking tag
		}else{
			$tmpTag = TranslateHelper::t('添加标签');
			$HtmlStr .= self::_generateTagIconHtml('egicon-flag-gray',$tmpTag , $orderid);
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
		$TagList = Ordertag::find()->where($condition)
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
	static public function getTrackingTagsByOrderId($orderid){
		$TrackingTagsList = LtOrderTags::find()->where(['order_id'=>$orderid])->asArray()->all();
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
	static public function getALlTagDataByOrderId($orderid){
		$TagIdList=[];
		if ($orderid instanceof OdOrder){
			//使用订单表的字段
			for($i=1;$i<=10;$i++){
				$key = 'customized_tag_'.$i;
				if ($orderid->$key =='Y'){
					$TagIdList [] = $i;
				}
			}
			
		}else{
			$TrackingTags = self::getTrackingTagsByOrderId($orderid);
			foreach($TrackingTags as $oneTrackingTag){
				$TagIdList [] = $oneTrackingTag['tag_id'];
			}
			
			
		}
		
		$TagData['all_tag'] = self::getTagByTagID();
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
				$model = Ordertag::findOne(['tag_id'=>$oneTag['tag_id']]);
			}
			
			if (empty($model)){
				if (!empty($oneTag['tag_name'])){
					$model = Ordertag::findOne(['tag_name'=>$oneTag['tag_name']]);
				}
			}
			
			if (empty($model)) $model = new Ordertag();
			
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
			$result['delete'] = Ordertag::deleteAll([ 'not in', 'tag_name', $TagNameList]);
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
	static public function saveOneOrderTag($order_id , $tag_name , $operation ,$color ){
		//检查标签  是否新标签
		$tagModel = Ordertag::find()->andWhere(['color' => $color])->one();
		$rt = true;
		if ($operation == "add"){
			if (empty($tag_name)) return ['success'=>false , 'message'=>'标签内容不能为空!'];
			
			if (empty($tagModel) ){
				//新标签并保存
				$tagModel = new Ordertag();
				$tagModel->tag_name = $tag_name;
				$tagModel->color = $color;
				
				//查询可用的order_indicator_code
				$code_list = [1,2,3,4,5,6,7,8,9,10];
				$order_indicator_code = 1;
				$user_code_list = array();
				$tags = Ordertag::find()->asArray()->all();
				foreach($tags as $tag){
					$user_code_list[] = $tag['order_indicator_code'];
				}
				foreach($code_list as $code){
					if(!in_array($code, $user_code_list)){
						$order_indicator_code = $code;
						break;
					}
				}
				
				$tagModel->order_indicator_code = $order_indicator_code;
				$rt = $tagModel->save();
			}
			
			if (! $rt) return ['success'=>false , 'message'=>'标签档案保存失败!'];
			
			//因为 现在自定义标签的种类不能修改和删除 ， 所以暂时使用 order_indicator_code = tag id 这个模式
			//Ordertag::updateAll(['order_indicator_code'=>$tagModel->tag_id],['tag_id'=>$tagModel->tag_id]);
			
			//添加标签  
			$TrackingTagsModel = LtOrderTags::find()->andWhere(['tag_id'=>$tagModel->tag_id , 'order_id'=>$order_id])->one();
			
			if (empty($TrackingTagsModel)){
				$TrackingTagsModel = new LtOrderTags();
				$TrackingTagsModel->tag_id = $tagModel->tag_id;
				$TrackingTagsModel->order_id = $order_id;
				$rt = $TrackingTagsModel->save();
				
				if (! $rt){
					return ['success'=>false , 'message'=>'标签保存失败!'.print_r($TrackingTagsModel->getErrors(),1)];
				}else{
					OdOrder::updateAll(['customized_tag_'.$tagModel->order_indicator_code =>'Y'],['order_id'=>$order_id]);
					
					//写入操作日志
					UserHelper::insertUserOperationLog('order', '添加订单自定义标签，订单号：'.ltrim($order_id, '0').'，颜色：'.$tagModel->color.'，内容：'.$tagModel->tag_name);
				}
			}
		}else if (!empty($tagModel) && $operation == "edit"){
			//当标签内容为空时，清空数据
			if(empty($tag_name)){
				//删除标签
				if(!$tagModel->delete()){
					return ['success'=>false , 'message'=>'标签删除失败!'];
				}
				LtOrderTags::deleteAll(['tag_id'=>$tagModel->tag_id ]);
				OdOrder::updateAll(['customized_tag_'.$tagModel->order_indicator_code =>''],[]);
				
				//写入操作日志
				UserHelper::insertUserOperationLog('order', '删除自定义标签，颜色：'.$tagModel->color.'，内容：'.$tagModel->tag_name);
			}
			else{
				if($tagModel->tag_name != $tag_name){
					$old_tag_name = $tagModel->tag_name;
					$tagModel->tag_name = $tag_name;
					if(!$tagModel->save()){
						return ['success'=>false , 'message'=>'标签档案保存失败!'];
					}
					//写入操作日志
					UserHelper::insertUserOperationLog('order', '修改自定义标签内容，颜色：'.$tagModel->color.'，修改前：'.$old_tag_name.'，修改后：'.$tagModel->tag_name);
				}
			}
		}else{
			//只有打到对应 的标签 才进行删除 
			if (! empty($tagModel) ){
				//删除标签
				$rt = LtOrderTags::deleteAll(['order_id'=>$order_id , 'tag_id'=>$tagModel->tag_id ]);
				OdOrder::updateAll(['customized_tag_'.$tagModel->order_indicator_code =>''],['order_id'=>$order_id]);
				
				if (! $rt) {
					return ['success'=>false , 'message'=>'标签删除失败!'];
				}
				
				//写入操作日志
				UserHelper::insertUserOperationLog('order', '移除订单自定义标签，订单号：'.ltrim($order_id, '0').'，颜色：'.$tagModel->color.'，内容：'.$tagModel->tag_name);
			}
		}
		
		return ['success'=>true , 'message'=>'操作成功!'];
		
	}//end of saveOneTrackingTag
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存order 系统标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 *				$tag_code				string 			系统标签code
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *				success					boolean			执行结果true为成功， false 为失败
	 *				message					string			执行失败的提示
	 *				code					int				200为正常， 400 为异常
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderSysTag($order_id , $tag_code){
		
		//1.检查tag code 是否有效
		if (! key_exists($tag_code, self::$OrderSysTagMapping)){
			return ['success'=>false , 'message'=>'tag code 无效' , 'code'=>400];
		}
		
		//2.检查该标签 是滞存在
		$model = OrderSystagsMapping::find()->where(['order_id'=>$order_id , 'tag_code'=>$tag_code])->all();
		//var_dump($model);
		if (!empty($model)){
			return ['success'=>false , 'message'=>self::$OrderSysTagMapping[$tag_code].' 已经添加,请不要重复添加！', 'code'=>200];
		}else{
			$model = new OrderSystagsMapping();
			$model->tag_code = $tag_code;
			$model->order_id = $order_id;
			if ($model->save(false)){
				//保存成功
				return ['success'=>true , 'message'=>'', 'code'=>200];
			}else{
				//保存失败
				$err_msg = "";
				foreach($model->errors as $sub_err_msg){
					$err_msg .=$sub_err_msg;
				}
				return ['success'=>false , 'message'=>$err_msg, 'code'=>400];
			}
		}
		
	}//end of setOrderSysTag
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除订单系统标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 *				$tag_code				string 			系统标签code
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *				success					boolean			执行结果true为成功， false 为失败
	 *				message					string			执行失败的提示
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function DelOrderSysTag($order_id , $tag_code){
		if ($tag_code =='all'){
			$param = ['order_id'=>$order_id ];
		}else{
			$param = ['order_id'=>$order_id , 'tag_code'=>$tag_code ];
		}
		$effect = OrderSystagsMapping::deleteAll($param);
		if (!empty($effect)){
			return ['success'=>true,'message'=>''];
		}else{
			return ['success'=>false,'message'=>'该标签已经删除了，请不要重复删除'];
		}
	}//end of DelOrderSysTag
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 检查订单系统标签
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 *				$tag_code				string 			系统标签code
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *				success					boolean			执行结果true为成功， false 为失败
	 *				message					string			执行失败的提示
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function IsExistOrderSysTag($order_id , $tag_code){
		//检查该标签 是滞存在
		$model = OrderSystagsMapping::find()->where(['order_id'=>$order_id , 'tag_code'=>$tag_code])->all();
		
		return (!empty($model));
	}//end of IsExistOrderSysTag
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 是否不合并发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 *				$tag_code				string 			系统标签code
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *				success					boolean			执行结果true为成功， false 为失败
	 *				message					string			执行失败的提示
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isSkipMergeOrder($order_id){
		$tag_code = 'skip_merge';
		return self::IsExistOrderSysTag($order_id, $tag_code);
	}//end of isSkipMergeOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 是否虚假发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 +---------------------------------------------------------------------------------------------
	 * @return 								boolean			true为虚假发货订单， false 为不是虚假发货订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isEagleUnshipOrder($order_id){
		$tag_code = 'sys_unshipped_tag';
		//2.检查该标签 是滞存在
		//$model = OrderSystagsMapping::find()->where(['order_id'=>$order_id , 'tag_code'=>$tag_code])->all();
		
		return self::IsExistOrderSysTag($order_id, $tag_code);
	}//end of isEagleUnshipOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据小老板订单号生成 自定义标签字符串
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  		$order_id				string			小老板订单号
	 +---------------------------------------------------------------------------------------------
	 * @return 								boolean			true为虚假发货订单， false 为不是虚假发货订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getAllTagStrByOrderId($order_id , $separator=','){
		$tagRt = self::getALlTagDataByOrderId($order_id);
		$str = '';
		foreach($tagRt['all_tag'] as $tmp){
			if (in_array($tmp['tag_id'],$tagRt['all_select_tag_id']) ){
				if (!empty($str)) $str .=$separator;
				$str .= $tmp['tag_name'];
			}
		}
		return $str;
	}
	
}//end of class
?>