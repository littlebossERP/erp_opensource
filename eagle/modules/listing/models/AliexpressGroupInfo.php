<?php namespace eagle\modules\listing\models;
	use eagle\modules\listing\helpers\AlipressApiHelper;
class AliexpressGroupInfo extends \eagle\models\listing\AliexpressGroupInfo 
{

	function getChildren(){

		
	}

	function getAllGroups($selleruserid){
		return $groups = AlipressApiHelper::getProductGroupList($selleruserid);
	}
}