<?php
namespace eagle\modules\listing\helpers;

use yii\base\Controller;
use eagle\modules\listing\models\EbayItem;
class EbayVisibleTemplateHelper
{
	// 样板item信息
	public static function getItemInfo(){
		$description_html = '
				<ul>
					<li>Luxury <b>satin</b> gift bags</li>
					<li>High quality silky satin used</li>
					<li>Ideal for jewellery pouches or wedding favour bags</li>
					<li>Also available in more color choices, please see our chart
					below</li>
					<li>Other size available, detail shown in size chart below</li>
					<li>Size measured from outside, internal size would be smaller</li>
					<li>Drawstring area takes at least one inch to close</li>
				</ul>
				<br>
				<br>
				<strong>
				<p class="master_size_string">Size</p>
				</strong>
				<ul class="master_size_string2">
					<li>
					<div class="descdiv">Approx. 10.5 x 14 cm or 4 x 5.5 inch</div>
					</li>
					<li>
					<div class="descdiv">(with 1 inch gusseted bottom)</div>
					</li>
				</ul>
				<br>
				<strong>Package includes</strong>
				<li>200 Bags</li>
				<br>
				<table class="desc_details  m_desc_details">
					<tbody>
					<tr>
						<td style="vertical-align: top;"><strong>
							<p class="master_Desc_string">Model</p></strong>
						</td>
						<td style="vertical-align: top;">
							<p id="master_Desc_string2">&nbsp;&nbsp;sample product code</p>
						</td>
					</tr>
					</tbody>
				</table>
				';
		$itemInfo = array(
			"switchType"=>"layout_left",
			"productType"=>"product_layout_left",
			"imagesUrlArr"=>array(
				\Yii::$app->params['hostInfo']."/images/ebay/template/sample/1.jpg",
				\Yii::$app->params['hostInfo']."/images/ebay/template/sample/2.jpg",
				\Yii::$app->params['hostInfo']."/images/ebay/template/sample/3.jpg",
				\Yii::$app->params['hostInfo']."/images/ebay/template/sample/4.jpg",
				\Yii::$app->params['hostInfo']."/images/ebay/template/sample/5.jpg",
				\Yii::$app->params['hostInfo']."/images/ebay/template/sample/1.jpg",
			),
			"crossSellArr"=>array(
				Array(
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 55,
					"picture" => "http://img02.taobaocdn.com/imgextra/i2/435878238/TB25pbGbpXXXXapXXXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array(
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 10,
					"picture" => "http://img01.taobaocdn.com/imgextra/i1/435878238/TB2r4jybpXXXXbiXpXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array(
					"title" => "Zalora代购包邮",
					"price" => 60,
					"picture" => "http://img02.taobaocdn.com/imgextra/i2/435878238/TB2gorLbpXXXXXdXXXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array(
					"title" => "Zalora代购包邮",
					"price" => 65,
					"picture" => "http://img03.taobaocdn.com/imgextra/i3/435878238/TB2tt_AbpXXXXaJXpXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array (
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 65,
					"picture" => "http://img02.taobaocdn.com/imgextra/i2/435878238/TB2jRzJbpXXXXaeXXXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array (
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 10,
					"picture" => "http://img04.taobaocdn.com/imgextra/i4/435878238/TB2Nc2ybpXXXXa8XpXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array (
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 100,
					"picture" => "http://img02.taobaocdn.com/imgextra/i2/435878238/TB2U4_CbpXXXXXEXpXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array(
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 20,
					"picture" => "http://img02.taobaocdn.com/imgextra/i2/435878238/TB2U4_CbpXXXXXEXpXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				),

				Array (
					"title" => "Zalora代购包邮 韩国包Lavina高菱格心型线金锁链带单肩斜挎小包",
					"price" => 30,
					"picture" => "http://img02.taobaocdn.com/imgextra/i2/435878238/TB2U4_CbpXXXXXEXpXXXXXXXXXX_!!435878238.jpg",
					"url" => "http://hk.taobao.com/?spm=a213y.6633709.a214bu1.14.P39xjJ&cat=16&at=13953&user_type_enable=true#!http://list.taobao.com/market/221/nvzhuang2011a.htm?cat=51108009&at=13953&user_type_enable=true&json=on&_input_charset=utf-8&q=",
					"icon" => "$",
				)
			),
			"title"=>"200 Burgundy Silky Satin Wedding Favor Gift Bag 10x14cm",
			"description"=>$description_html,
			"itemdescription_listing"=>$description_html,
		);
		return $itemInfo;
	}

	public static function getFinalTemplateHtml($itemInfo = array() , $mytemplate_obj = NUll ){
		if(is_array($itemInfo) && empty($itemInfo) || empty($mytemplate_obj))
			return "";

		$templateInfo = array();
		$allItem = array();
		$infodetclass = array();
		$switchType = "layout_left";
		$productType = "product_layout_left";
		$newListItem = EbayItem::find()->where(['listingstatus'=> 'Active'])->orderBy('starttime desc')->asArray()->one();

		$templateInfo = json_decode($mytemplate_obj->content,true);


		if(!empty($templateInfo)){
			foreach ($templateInfo['allItem'] as $value){
				$allItem[$value['name']] = $value['value'];
			}
			foreach ($templateInfo['infodetclass'] as $value){
				$infodetclass[$value['name']] = $value['value'];
			}
			if(!empty($templateInfo['switchType'])){
				$switchType = $templateInfo['switchType'];
			}
			if(!empty($templateInfo['productType'])){
				$productType = $templateInfo['productType'];
			}
		}

		$layoutSetting = explode("_", $allItem['layout_style_name']);
		$sideBarPosition = strtolower($layoutSetting[0]);
		//目前仅支持sidebar 左侧排版
		$layoutSetting[0] = "left";
		$sideBarPosition = $layoutSetting[0];


		$fileRoot = \Yii::getAlias('@eagle/modules/listing/views/ebay-template').DIRECTORY_SEPARATOR;
		$fileExt = ".php";
		if("right" == $sideBarPosition){
			$templateFile = $fileRoot."finalTemplate".DIRECTORY_SEPARATOR."finalRightSideTemplate".$fileExt;
		}elseif ("none" == $sideBarPosition){
			$templateFile = $fileRoot."finalTemplate".DIRECTORY_SEPARATOR."finalNoneSideTemplate".$fileExt;
		}else {// 默认左排版
			$templateFile = $fileRoot."finalTemplate".DIRECTORY_SEPARATOR."finalLeftSideTemplate".$fileExt;
			$layoutSetting[0] = "left";
			$allItem['layout_style_name'] = implode("_", $layoutSetting);
		}
		$templateInfo['fileRoot'] = $fileRoot;
		$templateInfo['fileExt'] = $fileExt;
		$templateInfo['allItem'] = $allItem;
		$templateInfo['infodetclass'] = $infodetclass;
		$templateInfo['itemInfo'] = $itemInfo;
		$templateInfo['productType'] = $productType;
		$templateInfo['switchType'] = $switchType;
		$templateInfo['newListItem'] = $newListItem;

		$controller = new Controller("ebayTemplate" , "listing");
		return $controller->renderFile($templateFile, $templateInfo);
	}
}
?>