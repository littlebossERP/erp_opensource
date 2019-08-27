<?php
namespace eagle\modules\listing\apihelpers;

use eagle\modules\listing\helpers\EbayVisibleTemplateHelper;
class EbayVisibleTemplateApiHelper
{
	// 样板item信息
	public static function getItemInfo(){
		return EbayVisibleTemplateHelper::getItemInfo();
	}
	
	
	public static function getFinalTemplateHtml($itemInfo = array() , $mytemplate_obj = NUll ){
		if(is_array($itemInfo) && empty($itemInfo) || empty($mytemplate_obj))
			return "";
		
		return EbayVisibleTemplateHelper::getFinalTemplateHtml($itemInfo , $mytemplate_obj);
	}
}
?>