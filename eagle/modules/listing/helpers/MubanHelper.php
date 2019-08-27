<?php
namespace eagle\modules\listing\helpers;
use \Yii;
use eagle\modules\listing\models\Mytemplate;
use eagle\modules\listing\models\EbaySalesInformation;
use eagle\modules\listing\models\EbayCrossselling;
use eagle\modules\listing\models\EbayCrosssellingItem;
use eagle\models\EbaySite;
use common\helpers\Helper_Array;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: fanjs
+----------------------------------------------------------------------
| Create Date: 2014-08-01
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 刊登模块模板业务
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		fanjs
 +------------------------------------------------------------------------------
 */
class MubanHelper {
	
	//商品图片上传大小限制
	public static $photoMaxSize = 2097152; // 2M
	private static $photoMime = array ( 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg', 'image/gif' => 'gif', 'image/tiff' =>'tif','image/bmp' =>'bmp');
	/**
	 +----------------------------------------------------------
	 * 获取订单列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param page			当前页
	 * @param rows			每页行数
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				订单数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	**/
	public static function getListData($page, $rows, $sort, $order, $queryString) {
		$params = array();
		if(!empty($queryString)) {
			foreach($queryString as $k => $v) {
					$params[$k] = "= '{$v}'";
			}
		}
		$criteria = self::createMubanCriteria($params);
		$criteria->limit = $rows;
		$criteria->offset = ($page - 1) * $rows;
		$criteria->order = "$sort $order";//排序条件

		//记录总行数
		$result['total'] = EbayMuban::model()->count($criteria);
		$result['rows'] = array_values(self::getMubanData($criteria, false));
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 创建查询条件对象
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		数组条件对象	array('id'=>'>0','id'=>'IN (1,2)')
	 +----------------------------------------------------------
	 * @return				条件CDbCriteria对象
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	 **/
	public static function createMubanCriteria($params) {
		$criteria = new CDbCriteria();
		$modelAttr = EbayMuban::model()->attributes;
		foreach($params as $k => $v) {
			if(array_key_exists($k, $modelAttr)) {
				$criteria->addCondition("t.$k $v");
			}
		}
		return $criteria;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取刊登模板数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param criteria			查询条件
	 +----------------------------------------------------------
	 * @return				包裹详细数据数组idArr
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	 **/
	public static function getMubanData($criteria,$detail, $isObject = false) {
		$obj = EbayMuban::model();
		if($detail) {
			$obj->with('detail');
		}
		$result = array();
		$tempResult = EbayMuban::model()->findAll($criteria);
		if($isObject) {
			return $tempResult;
		}
		foreach($tempResult as $k => $v) {
			$muban = $v->attributes;
			$muban['mainimg']=strlen($muban['mainimg'])>5?$muban['mainimg']:"/images/littleboss.jpg";
			$siteIdList = CommonHelper::getEbaySiteIdList();
			$platformInfo = $siteIdList[$muban['siteid']];
			$muban['platform'] = $platformInfo['zh'].'/'.$platformInfo['en'].'('.$platformInfo['code'].')';
			$result[$k] = $muban;
			if($detail) {
				foreach($v->detail as $ik => $iv) {
					$result[$k]['detail'][$ik] = $iv->attributes;
				}
			}
		}
		return $result;
	}
	
	/**
	 *  组装刊登描述-----对于选择了可视化编辑的风格模板的情况
	 *  xjq 2014-11-17
	 */	
	public static function buildVisibleDescription($selleruserid,$values,$itemTitle,$description,$mytemplate_obj,$itemdescription) {

		//1. 获取可视化的风格模板的内容
		$templateContentJson= $mytemplate_obj->content; //保存可视化编辑中 input 等元素内容的数据信息--json格式
		
		//2. 获取所有变量中的内容
		$allVariablesInfo=array();// 变量包括：   产品title,文字描述, 产品图片，crosssell的信息
		if ($itemTitle<>NULL) $allVariablesInfo["title"]=$itemTitle; else $allVariablesInfo["title"]="";
		if ($description<>NULL) $allVariablesInfo["description"]=$description; else $allVariablesInfo["description"]="";
		if ($itemdescription<>NULL) $allVariablesInfo["itemdescription_listing"]=$itemdescription; else $allVariablesInfo["itemdescription_listing"]="";
		if (isset($values["imgurl"])) {
			$allVariablesInfo["imagesUrlArr"]=$values["imgurl"];
		}else{
			$allVariablesInfo["imagesUrlArr"]=array();
		} //array的形式。      第一个元素是大图
		//get crosssell 信息----------例子：获取CROSSSELL2的图片信息  -------$allVariablesInfo["crossSellArr"][1]["picture"]
		$allVariablesInfo["crossSellArr"]=array();
		if (isset($values['crossselling'])&&(int)$values['crossselling']>0){
			$csitems=EbayCrosssellingItem::find()->where(['crosssellingid'=>$values['crossselling']])->orderBy('sort ASC')->all();
			
			if (count($csitems)>0){
				foreach ($csitems as $csi){
			       //    data is an Array---- array("picture"=>"","url"=>"")
			       $allVariablesInfo["crossSellArr"][]=$csi->data;
				}
			}
		}
		//get custorm
		$allVariablesInfo["crossSellArr_two"]=array();
		if (isset($values['crossselling_two'])&&(int)$values['crossselling_two']>0){
			$csitems=EbayCrosssellingItem::find()->where(['crosssellingid'=>$values['crossselling_two']])->orderBy('sort ASC')->all();

			if (count($csitems)>0){
				foreach ($csitems as $csi){
					//    data is an Array---- array("picture"=>"","url"=>"")
					$allVariablesInfo["crossSellArr_two"][]=$csi->data;
				}
			}
		}
//		Yii::log("buildDescription allVariablesInfo:".print_r($allVariablesInfo,true),"info","");
		
		$template = EbayVisibleTemplateHelper::getFinalTemplateHtml($allVariablesInfo,$mytemplate_obj);		
		// 添加小老板的logo到刊登信息中
		$logostr = '<div style="margin-top:15px;margin-bottom:15px;"><center><a href="http://www.littleboss.com" target="_new"><img border=0 width=88 height=33 src="http://www.littleboss.com/images/project/application/index/index/logo.jpg"></a></center></div>';
		
		Yii::info("buildDescription allVariablesInfo:".$template.$logostr,"file");
		
		return $template.=$logostr;

	}
	
	/**
	 * 组装刊登描述
	 * million 2014-10-30
	 */
	public static function buildDescription($selleruserid,$values,$itemTitle=NULL,$description=NULL,$itemdescripition=NULL) {
		Yii::info("Entering buildDescription ","info","");
		Yii::info("buildDescription value:".print_r($values,true),"info","");
		
		//判断是否选择模板如果未选择description原样返回
		if(isset($values['template']) && (int)$values['template']>0){
			$mytemplate_obj = Mytemplate::findOne($values['template']);
			if (empty($mytemplate_obj)){
				return $description;
			}
			//判断是否可视化风格模板---type  0 简单风格模板; 1 可视化风格模板
			if ($mytemplate_obj->type==1){				
				return self::buildVisibleDescription($selleruserid,$values,$itemTitle,$description,$mytemplate_obj,$itemdescripition);
			}
			
			
			$template= $mytemplate_obj->content;
			self::replaceMubanTemplate ( '[TITLE]', $itemTitle, $template );
			self::replaceMubanTemplate ( "[DESCRIPTION_AND_IMAGES]", $description, $template );
			self::replaceMubanTemplate ( '[SKU]', $values['sku'], $template );
			if (isset($values['basicinfo']) && (int)$values['basicinfo']>0) {
				$basicinfo = EbaySalesInformation::findOne($values['basicinfo']);
				if (!empty($basicinfo)){
					self::replaceMubanTemplate ( '[DELIVERY_DETAILS]', $basicinfo->delivery_details, $template );
					self::replaceMubanTemplate ( '[PAYMENT]', $basicinfo->payment, $template );
					self::replaceMubanTemplate ( '[TERMS_OF_SALES]', $basicinfo->terms_of_sales, $template );
					self::replaceMubanTemplate ( '[ABOUT_US]', $basicinfo->about_us, $template );
					self::replaceMubanTemplate ( '[CONTACT_US]', $basicinfo->contact_us, $template );
				}else{
					self::replaceMubanTemplate ( '[DELIVERY_DETAILS]', '', $template );
					self::replaceMubanTemplate ( '[PAYMENT]', '', $template );
					self::replaceMubanTemplate ( '[TERMS_OF_SALES]', '', $template );
					self::replaceMubanTemplate ( '[ABOUT_US]', '', $template );
					self::replaceMubanTemplate ( '[CONTACT_US]', '', $template );
				}
			} else {
				self::replaceMubanTemplate ( '[DELIVERY_DETAILS]', '', $template );
				self::replaceMubanTemplate ( '[PAYMENT]', '', $template );
				self::replaceMubanTemplate ( '[TERMS_OF_SALES]', '', $template );
				self::replaceMubanTemplate ( '[ABOUT_US]', '', $template );
				self::replaceMubanTemplate ( '[CONTACT_US]', '', $template );
			}
			
			//替换crossselling的值
			//@author fanjs
			if (isset($values['crossselling'])&&(int)$values['crossselling']>0){
				$csitems=EbayCrosssellingItem::find()->where(['crosssellingid'=>$values['crossselling']])->orderBy('sort ASC')->all();
				if (count($csitems)>0){
					$i=1;
					foreach ($csitems as $csi){
						$title1 = self::utf8_substr($csi->data['title'],0,30);
						self::replaceMubanTemplate ( '[image'.$i.']', $csi->html, $template );
						self::replaceMubanTemplate ( '[title'.$i.']', $title1, $template );
						self::replaceMubanTemplate ( '[price'.$i.']', $csi->data['icon'].' '.$csi->data['price'], $template );
						$i++;
					}
				}	
			}
			
			$logostr = '<div style="margin-top:15px;margin-bottom:15px;"><center><a href="http://www.littleboss.com" target="_new"><img border=0 width=88 height=33 src="http://www.littleboss.com/images/logo_2.png"></a></center></div>';
			return $template.=$logostr;
		}else{
			return $description;
		}
		
	}
	/**
	 * 自定义标记，便于以后对描述内容解析
	 * million 2014-10-30
	 */
	static function replaceMubanTemplate($key,$value,&$template){
		$realkey=str_replace(array('[',']'),'',$key);
		$value2='<!--['.$realkey.'_LITTLEBOSS_BEGIN]-->'.$value.'<!--['.$realkey.'_LITTLEBOSS_END]-->';
		$template=self::str_replace_once($key,$value2,$template);
		return $template;
	}
	
	/**
	 * 只替换1次
	 * million 2014-10-30
	 */
	static function str_replace_once($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		if ($pos === false) {
			return $haystack;
		}
		return substr_replace($haystack, $replace, $pos, strlen($needle));
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 保存原图到数据库
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param source		图片名称
	 * @param path			图片保存路径
	 +----------------------------------------------------------
	 * @return				是否保存成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl 	2014/12/3				初始化
	 +----------------------------------------------------------
	 **/
	public static function saveOriginalImg($tmpFilePath, $filePath, $urlPath, $fileMime) {
		$fileName = base64_encode(time().rand(0, 1000)) . '.' . self::$photoMime[$fileMime];
		$desFilePath = $filePath. $fileName;
	
		if(file_exists($desFilePath) || move_uploaded_file($tmpFilePath, $desFilePath)) {
			return $urlPath.$fileName;
		}
		return FALSE;
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 判断保存图片的路径是否存在，不存在则创建
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param dir			需要判断的路径字符串
	 +----------------------------------------------------------
	 * @return				路径是否存在
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl 	2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public static function mkDirIfNotExist($dir) {
		if (! file_exists ( $dir )) {
			if (! mkdir ( $dir, 0777, true )) {
				throw new Exception ( 'create folder fail' );
			} else {
				return true;
			}
		}
		return false;
	}
	
	public static function utf8_substr($str,$start=0) {
		if(empty($str)){
			return false;
		}
		$null = "";
		preg_match_all("/./u", $str, $ar);
		if(func_num_args() >= 3) {
			$end = func_get_arg(2);
			return join($null, array_slice($ar[0],$start,$end));
		}
		else {
			return join($null, array_slice($ar[0],$start));
		}
	}
	
	/**
	 * 根据Item的内容生成刊登范本
	 * @param Ebay_Item $item
	 * @param $data 范本最基础的信息
	 * @author fanjs
	 */
	static function _update_muban_by_item($item,$data){
		$siteid = EbaySite::findOne(['site'=>$item->site])->siteid;
	
		$MI = $item;
		$MID = $item->detail;
		$itemdescription = $MID->itemdescription;
		
		$specific=$MID['itemspecifics'];
		$arr_itemsp = [];
		if (isset($specific['NameValueList']['Name'])){
			$specific['NameValueList']=array($specific['NameValueList']);
		}
		if (isset($specific['NameValueList'])){
			foreach ($specific['NameValueList'] as $val){
				$arr_itemsp[$val['Name']] = is_array($val['Value'])?$val['Value']['0']:$val['Value'];
			}
		}
	
		$waitmerge = array(
			'itemtitle' => $MI['itemtitle'] ,
			'itemtitle2' => $MID['subtitle'] ,  //
			'primarycategory' => $MID['primarycategory'],
			'secondarycategory' => $MID['secondarycategory'] ,//
			'quantity' => $MI['quantity'] ,
			'lotsize' => $MID['lotsize'] ,
			'listingduration' => $MI['listingduration'] ,
			'startprice' => $MI['startprice'] ,
			'buyitnowprice' => $MI['buyitnowprice'] ,
			'itemdescription' => $itemdescription ,
			'listingtype' => $MI['listingtype']=='Chinese'?'Chinese':'FixedPriceItem' ,
			'siteid' => $siteid ,
			'listingenhancement' => $MID['listingenhancement'],
			'hitcounter' => $MID['hitcounter'] ,
			'paymentmethods' => (count($MID['paymentmethods'])||strlen($MID['paymentmethods']))?$MID['paymentmethods']:array('PayPal'),
			'location' => $MID['location'] ,
			'country' => $MID['country'] ,
			'gallery' => $MID['gallery'] ,
			'dispatchtime' => $MI['dispatchtime'] ,  //
			'return_policy' => $MID['returnpolicy'],
			'conditionid'=>$MID['conditionid'],
			'sku'=>$MI['sku'],
			'bestoffer'=>$MID['bestoffer'],
			'bestofferprice'=>$MID['bestofferprice'],
			'storecategoryid'=>$MID['storecategoryid'],
			'storecategory2id'=>$MID['storecategory2id'],
			'specific'=>$arr_itemsp,
			'vatpercent'=>$MID['vatpercent'],
			'outofstockcontrol'=>$MI['outofstockcontrol'],
			'epid'=>$MID['epid'],
			'ean'=>$MID['ean'],
			'isbn'=>$MID['isbn'],
			'upc'=>$MID['upc'],
		);
		
		$data = array_merge($data,$waitmerge);
		if (strlen($MI->paypal)){
			$data['paypal']=$MI->paypal;
		}
		$data['desc']='ItemID:'.$MI['itemid'].'生成范本';
	
	
		$data['imgurl']=$MID['imgurl'];
		if (count($MID['imgurl'])){
			$data['mainimg']=$MID['imgurl']['0'];
		}
	
		$shippingdetails = $MID['shippingdetails'];
		if($MI['listingtype']!='Chinese'){
			$data['buyitnowprice']=$MI['startprice'];
			$data['startprice']='0.00';
		}
		// 物流转换
		if (isset ( $shippingdetails ['ShippingServiceOptions'] ['ShippingService'] )) {
			$shippingdetails ['ShippingServiceOptions'] = array ($shippingdetails ['ShippingServiceOptions'] );
			
			foreach($shippingdetails['ShippingServiceOptions'] as $k=>$s){
				if ($s['ShippingService']){
					$shippingdetails['ShippingServiceOptions'][$k]['ShippingService']= $s['ShippingService'];
					$shippingdetails['ShippingServiceOptions'][$k]['ShippingServiceCost']= $s['ShippingServiceCost'];
					if (isset($s['ShippingServiceAdditionalCost'])){
						$shippingdetails['ShippingServiceOptions'][$k]['ShippingServiceAdditionalCost']= $s['ShippingServiceAdditionalCost'];
					}
					if(empty($s['ShipToLocation'])){
						$shippingdetails['ShippingServiceOptions'][$k]['ShipToLocation']='None';
					}else{
						$shippingdetails['ShippingServiceOptions'][$k]['ShipToLocation']= @$s['ShipToLocation'];
					}
				}
			}
		}
		if (isset ( $shippingdetails ['InternationalShippingServiceOption'] ['ShippingService'] )) {
			$shippingdetails ['InternationalShippingServiceOption'] = array ( $shippingdetails ['InternationalShippingServiceOption'] );
		}
		//         $i=5;
		if (isset($shippingdetails['InternationalShippingServiceOption'])&&count($shippingdetails['InternationalShippingServiceOption'])){
			foreach($shippingdetails['InternationalShippingServiceOption'] as $k=> $s){
				if ($s['ShippingService']){
					$shippingdetails['InternationalShippingServiceOption'][$k]['ShippingService']= $s['ShippingService'];
					$shippingdetails['InternationalShippingServiceOption'][$k]['ShippingServiceCost']= $s['ShippingServiceCost'];
					if (isset($s['ShippingServiceAdditionalCost'])){
						$shippingdetails['InternationalShippingServiceOption'][$k]['ShippingServiceAdditionalCost']= $s['ShippingServiceAdditionalCost'];
					}
					if(empty($s['ShipToLocation'])){
						$shippingdetails['InternationalShippingServiceOption'][$k]['ShipToLocation']= 'None';
					}else{
						$shippingdetails['InternationalShippingServiceOption'][$k]['ShipToLocation']= @$s['ShipToLocation'];
					}
				}
			}
		}
		if (isset($shippingdetails['ExcludeShipToLocation']['SellerExcludeShipToLocationsPreference']) && ($shippingdetails['ExcludeShipToLocation']['SellerExcludeShipToLocationsPreference']=='true' || $shippingdetails['ExcludeShipToLocation']['SellerExcludeShipToLocationsPreference']===true)){
			//如果是根据ebay帐号全局设置屏蔽目的地，取消记录
			unset($shippingdetails['ExcludeShipToLocation']);
		}
		$variations=$MID['variation'];
		$m_variations=array();
		if (count($variations)){
			if (isset($variations['Pictures']) && is_array($variations['Pictures'])){
				if (isset($variations['Pictures']['VariationSpecificName'])){
					$_tmp=$variations['Pictures'];
					unset($variations['Pictures']);
					$variations['Pictures'][0]=$_tmp;
				}
				$m_variations['assoc_pic_key']=$variations['Pictures'][0]['VariationSpecificName'];
				if (isset($variations['Pictures'][0]['VariationSpecificPictureSet']['PictureURL'])){
					$variations['Pictures'][0]['VariationSpecificPictureSet']=array($variations['Pictures'][0]['VariationSpecificPictureSet']);
				}
				$pvs=$variations['Pictures'][0]['VariationSpecificPictureSet'];
			}
			if (isset($variations['Variation']['SKU'])){
				$variations['Variation']=array($variations['Variation']);
			}
			foreach ($variations['Variation'] as $v){
				if (is_array($v['StartPrice'])){
					$v['StartPrice'] = $v['StartPrice']['Value'];
				}
				if (isset($v['VariationSpecifics']['NameValueList']['Name'])){
					$v['VariationSpecifics']['NameValueList']=array($v['VariationSpecifics']['NameValueList']);
				}
				if (isset($v['SellingStatus']['QuantitySold']) && $v['SellingStatus']['QuantitySold']>0){
					$v['Quantity']-=$v['SellingStatus']['QuantitySold'];
				}
				$m_variations['Variation'][]=$v;
				$pv_item=array(
						'VariationSpecificPictureSet'=>array(
								'PictureURL'=>'',
						),
						'Value'=>'none_skip'
				);
				if (!empty($pvs)){//原来老范count($pvs)
					foreach ($pvs as $pv){
						if (isset($pv['PictureURL'])&&!is_array($pv['PictureURL'])){
							$_tmp=$pv['PictureURL'];
							unset($pv['PictureURL']);
							$pv['PictureURL'][0]=$_tmp;
						}
						foreach ($v['VariationSpecifics']['NameValueList'] as $nv){
							if ($nv['Name']!=$m_variations['assoc_pic_key']){
								continue;
							}
							if (is_array($nv['Value'])){
								$nv['Value'] = $nv['Value'][0];
							}
							if(is_array($pv)&&$pv['VariationSpecificValue']==$nv['Value']){
// 								@$m_variations['assoc_pic_count']++;
// 								$pv_item=array(
// 										'VariationSpecificPictureSet'=>array(
// 												'PictureURL'=>$pv['PictureURL'][0]
// 										),
// 										'Value'=>$nv['Value']
// 								);
                                if(is_array($pv['PictureURL'])){
                                   @$m_variations['assoc_pic_count'] = @$m_variations['assoc_pic_count'] + count($pv['PictureURL']); 
                                }
								$pv_item=array(
								    'VariationSpecificPictureSet'=>array(
								        'PictureURL'=>$pv['PictureURL']//保存为数组
								    ),
								    'Value'=>$nv['Value']
								);
							}
						}
					}
				}
				$m_variations['Pictures'][]=$pv_item;
			}
			//改[Variation][VariationSpecifics][NameValueList]的value值不为数组
			foreach ($variations['Variation'] as &$v_val){
			    if (isset($v_val['VariationSpecifics']['NameValueList']['Name'])){
			        $v_val['VariationSpecifics']['NameValueList']=array($v_val['VariationSpecifics']['NameValueList']);
			    }
			    foreach ($v_val['VariationSpecifics']['NameValueList'] as &$kk){
			        if (is_array($kk['Value'])){
			            $kk['Value'] = $kk['Value'][0];
			        }
			    }
			}
			unset($v_val);
			unset($kk);
			if (!isset($variations['VariationSpecificsSet'])){
				$nvl=array();
				foreach ($variations['Variation'] as $v){
					if (isset($v['VariationSpecifics']['NameValueList']['Name'])){
						$v['VariationSpecifics']['NameValueList']=array($v['VariationSpecifics']['NameValueList']);
					}
					foreach ($v['VariationSpecifics']['NameValueList'] as $_nvl){
						$nvl[$_nvl['Name']][]=$_nvl['Value'];
					}
				}
				foreach ($nvl as &$vv){
					$vv=array_unique($vv);
				}
				foreach ($nvl as $nvl_name => $nvl_value){
					$variations['VariationSpecificsSet']['NameValueList'][]=array('Name'=>$nvl_name,'Value'=>$nvl_value);
				}
			}
			$m_variations['VariationSpecificsSet']=$variations['VariationSpecificsSet'];
			if (isset($m_variations['VariationSpecificsSet']['NameValueList']['Name'])){
				$m_variations['VariationSpecificsSet']['NameValueList']=array($m_variations['VariationSpecificsSet']['NameValueList']);
			}
			$data['variation']=$m_variations;
		}
		/**
		 * @todo 待增加ItemSpecific、买家限制、税率处理
		 */
		//买家限制自动补全
		$data['buyerrequirementdetails']=array(
				'LinkedPayPalAccount'=>'false',
				'MaximumBuyerPolicyViolations'=>array('Count'=>4,'Period'=>'Days_30'),
				'MaximumUnpaidItemStrikesInfo'=>array('Count'=>4,'Period'=>'Days_30'),
		);
		$data['shippingdetails'] = $shippingdetails;
		
		$data['privatelisting'] = $MID['privatelisting'];
		return $data;	
	}
}
