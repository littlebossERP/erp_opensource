<?php
namespace eagle\modules\util\helpers;


/*+------------------------------------------------------------------------------------------------
| 小老板                                                             																			  |
+--------------------------------------------------------------------------------------------------
| COPYRIGHT.  THE HUIYOU LTD.  ALL RIGHT RESERVED
|
| THIS SOURCE CODING, WHICH CONTAINS CONFIDENTIAL MATERIAL, IS PRIVATE AND CONFIDENTIAL
| AND IS THE PROPERTY AND COPYRIGHT OF THE HUIYOU LTD.
| IT IS NOT TO BE USED FOR ANY OTHER PURPOSES, COPIED, DISTRIBUTED OR TRANSMITTED IN ANY FORM
| OR BY ANY MEANS WITHOUT THE PRIOR WRITTEN CONSENT OF THE HUIYOU LTD.
| INFRINGEMENT OF COPYRIGHT IS A SERIOUS CIVIL AND CRIMINAL OFFENCE,
| WHICH CAN RESULT IN HEAVY FINES AND PAYMENT OF SUBSTANTIAL DAMAGES.
+--------------------------------------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )										  |
+--------------------------------------------------------------------------------------------------
| Created By: Yang ZengQiang (zengqiang.yang@witsion.com)
| Create Date: 2014-01-30
| Description: 
|             This is the helper of Invenotry module, it helps to load and save all 				
|			  inventory related data entities.
+--------------------------------------------------------------------------------------------------
| AMENDMENT HISTORY										 										  |
+--------------------------------------------------------------------------------------------------
| Amended By: ***
| Amended Date: ****-**-**
| PROGRAM PROBLEM REPORT/CHANGES REQUEST (PPCR) NO: 
| Description: 
|             ****
+--------------------------------------------------------------------------------------------------
*/

/**
 +------------------------------------------------------------------------------
 * Control Data retrieving utility
 +------------------------------------------------------------------------------
 * @category	Component
 * @package		utility
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
*/

class GetControlData {
	private static $process_id = 0;
	/**
	 +---------------------------------------------------------------------------------------------
	 * Pharse a string and check if any element match the key as id input
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  
	 * 1. Array with element of foramt like '1'=>"name A"
	 * 2. index ID
	 +---------------------------------------------------------------------------------------------
	 * @return				The value matching the id input.
	 *                      When the id is blank, return the whole array.
	 *                      When the id doesn't match any element key, return "(未定义)"
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getValueFromArray($array_defined, $index_id=''){
		//when there is $index_id  passed, return all possible values
		if ($index_id == '')
			$return_value = $array_defined;
		else{//when there is $index_id passed, return the exactly one
			if (!isset($array_defined["$index_id"]))
				$return_value = "(未定义)";
			else
				$return_value = $array_defined["$index_id"];
		}
		return 	$return_value;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get date time of now
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return				String format of now date time, e.g. : 2010-11-5 13:05:45
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getNowDateTime_str(){
		$date = new \DateTime();
		$now_str = $date->format('Y-m-d H:i:s');
		return $now_str ;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To create a process id if not created for this php thread.
	 * If already created, return the same job id
	 * So that we can know the serial func invokings are for a same process / user operation
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return				process Id created. Int type. e.g. 201405261325
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/05/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getProcessId($withUserId = 1){
		if (self::$process_id == 0){
			//create a process id
			$date = new \DateTime();
			self::$process_id = $date->getTimestamp();
			//put the userid in the end as well
			if ($withUserId == 1)
				self::$process_id = self::$process_id * 100 + \Yii::$app->user->id;
		}
		return self::$process_id ;
	}//end of function
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * To format the user name into models according to the user ids
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	$models                 array of models/sql row arrays
	 * #param   $user_id_field_name     field name of user id in the models,which will be 
	 * 									converted into user names
	 +---------------------------------------------------------------------------------------------
	 * @Invoke              GetControlData::formatModelsWithUserName($model_array,"capture_user_id");
	 * @return				$models, with user name converted
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/05/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function formatModelsWithUserName($models , $user_id_field_name){
		$models_with_user_name = array();
		foreach ($models as $aModel){
			if ( is_array($aModel))
				$attrs = $aModel;
			else
			$attrs = $aModel->attributes;
			
			$attrs[$user_id_field_name] = IndexHelper::getUserNameById($attrs[$user_id_field_name]);
			
			if ( is_array($aModel))
				$aModel = $attrs;
			else	
				$aModel->attributes = $attrs;
			
			$models_with_user_name[] = $aModel;
		}//end of each model
		return $models_with_user_name;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To get Nation code and name in Combo box format
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *			map.
	 *			e.g.
	 *			array( array('is_active'=>'Y','name'=>"正常开启"),
	 *				   array (...)
	 *				)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getNationComoBox(){
		$activeStatusIdNameArr=array();
		$activeStatusIdNameMap=StandardConst::$COUNTRIES_CODE_NAME_CN;
		foreach($activeStatusIdNameMap as $id=>$name)
		{
			$activeStatusIdNameArr[]=array('nation_code'=>$id,'nation_name'=>($id . " ".$name));
		}
		$activeStatusIdNameComoBox=$activeStatusIdNameArr;
		return $activeStatusIdNameComoBox;
	}
}

?>