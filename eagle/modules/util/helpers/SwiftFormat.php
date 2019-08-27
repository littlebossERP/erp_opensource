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
|             This is the Class of Swift Format Utility.
|             It manage the common used functions not realted to business logic
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
* 快速 格式化数据 工具类
+------------------------------------------------------------------------------
* @category		Components
* @package		utility
* @subpackage   Exception
* @version		1.0
+------------------------------------------------------------------------------
*/

class SwiftFormat {

	/**
	 +---------------------------------------------------------------------------------------------
	 * Convert Seperated String to format of SQL In syntax.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 1:$seperatedStr, e.g. ",,cg001,cg002," 
	 * 2:$seperator, e.g. ",", default = ","    
	 +---------------------------------------------------------------------------------------------
	 * @return		    Formated string like "'cg001','cg002'"		
	 * @Description		This is to parse the input string, sperate it with seperator and make it like
	 * 						"'cg001','cg002'" as return string
	 * 						Which is easy for SQL syntax
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cvtSepStrToSqlInSyntax($seperatedStr,$seperator=","){
		$ids_array = explode($seperator, $seperatedStr);
		$id_string = "";
		foreach($ids_array as $anId){
			if (trim($anId) <> '')
				$id_string .= ($id_string == "" ? "" : ",") . "'$anId'";
		}
		return $id_string;
	}

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Get total number of rows found by this SQL syntax.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * parm 1: $sql, e.g. "select sku,name from table A order by"
	 * parm 2: bind parameter array	, which is an array containing element like ("id"=> 2)
	 +---------------------------------------------------------------------------------------------
	 * @return			Number of total rows can be found by this sql
	 * @Description		This is to parse the sql syntax and format it to be like
	 * 						  "select count(*) from table A"\
	 * 						  And then the total result count will be returned
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getSqlResultTotalCount($sql, $bingParmValues = array()){
		$sql = trim(strtolower($sql));

		// 首先替换 \r\n 字符，因此它们不会被两次转换
		$sql = str_replace("\r\n"," ", $sql);
		$sql = str_replace("\r"," ", $sql);
		$sql = str_replace("\n"," ", $sql);
		$sql = str_replace("\t"," ", $sql);
		$sql = str_replace("  "," ", $sql);
		
		$pos = strpos($sql, " from");
		//where this sql syntax doesn't contain "select xxx from" this pattern, return 0
		if ($pos < 0 ) return 0;
		//convert the sql syntax to "select count(*), not selecting columns"
		$sql = "select count(*) from ( select 1  ". substr($sql,$pos) ;

		//remove the tail if there is " order by ...."
		$pos = strripos($sql, " order by ");
		if ($pos > 0)
			$sql = substr($sql,0,$pos);
		
		//remove the tail if there is " limit ...."
		$pos = strripos($sql, " limit ");
		if ($pos > 0)
			$sql = substr($sql,0,$pos);		
		
		$command = Yii::app()->subdb->createCommand($sql. " ) as AAA");
		//SysLogHelper::SysLog_Create("report",__CLASS__, __FUNCTION__,"","try to get total count:".$sql, "trace");
		//bind the parameter values
		foreach ($bingParmValues as $k=>$v){
			$command->bindValue(":$k",($v),PDO::PARAM_STR);
		}
		
		return $command->queryScalar();		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To check all elements in the array and subarray and trim the blanks
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * parm 1: 		array, called by reference
	 +---------------------------------------------------------------------------------------------
	 * @return			
	 * @Description		Recursive to call self to trim all elements and subarrays
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function arrayTrim(&$array1){
		foreach($array1 as $key => $value){	
			if (is_array($value)){
				//recursive doing that by refrence
				self::arrayTrim($value);
			}
			else{
				$array1[$key] = trim($value);
			}
		}
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * Query sql syntax and return the results with pagination, like model->query
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * db: 				db connection
	 * sql_str: 		sql syntax
	 * page:            show which page, default 1
	 * rows:            how many rows per page, default 50
	 *                  when 0, means no limit=One Page for all
	 * sort:            sort by what? default ""
	 * order_by:        when sort, asc or descending ? default ""=asc
	 * bindParmValues:	array of binding parameters
	 +---------------------------------------------------------------------------------------------
	 * @return			$result = array('rows'=>xxxx, 'total'=500)
	 * @Description		Query sql syntax and return the results with pagination, like model->query
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/07/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function querySQLWithPage($db, $sql, $page=1, $rows=50,$sort='',$order_by='',$bindParmValues = array()){
		$criteria = new CDbCriteria();
		$criteria->limit = $rows;
		$criteria->offset = ($page-1) * $rows;
		if (!empty($sort))
			$criteria->order = "$sort $order_by";//排序条件
		$sql .= (empty($criteria->order) ? "": " order by ".$criteria->order ) 
				.($criteria->limit==0  ? "" :  " LIMIT ".$criteria->limit ." OFFSET ".$criteria->offset ); //LIMIT 10 OFFSET 20 , e.g. page = 3
		
		$command = $db->createCommand($sql);
		
		foreach ($bindParmValues as $k=>$v){
			$command->bindValue(":$k",$v,PDO::PARAM_STR);
		}
		//SysLogHelper::SysLog_Create("report",__CLASS__, __FUNCTION__,"", $command->getText(), "trace");
		
		$result['rows'] = $command->queryAll();		
		//$result['total'] = count($result['rows']);
		$result['total'] = self::getSqlResultTotalCount($command->getText() , $bindParmValues );
		
		return $result;
	}
}//end of class

?>