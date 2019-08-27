<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace eagle\modules\util\models;

use Yii;
use yii\db\Connection;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\VarDumper;
use yii\log\DbTarget;

/**
 * DbTarget stores log messages in a database table.
 *
 * The database connection is specified by [[db]]. Database schema could be initialized by applying migration:
 *
 * ```
 * yii migrate --migrationPath=@yii/log/migrations/
 * ```
 *
 * If you don't want to use migration and need SQL instead, files for all databases are in migrations directory.
 *
 * You may change the name of the table used to store the data by setting [[logTable]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class EDbTarget extends DbTarget
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbTarget object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'db';
    /**
     * @var string name of the DB table to store cache content. Defaults to "log".
     */
    public $logTable = '{{%log}}';


    /**
     * Initializes the DbTarget component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DB connection.
     * @throws InvalidConfigException if [[db]] is invalid.
     */
/*    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }*/


    /**
     * Stores log messages to DB.
     */
    public function export()
    {
    /*    $tableName = $this->db->quoteTableName($this->logTable);
        $sql = "INSERT INTO $tableName ([[level]], [[category]], [[log_time]], [[prefix]], [[message]])
                VALUES (:level, :category, :log_time, :prefix, :message)";
        $command = $this->db->createCommand($sql);
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            if (!is_string($text)) {
                $text = VarDumper::export($text);
            }
            $command->bindValues([
                ':level' => $level,
                ':category' => $category,
                ':log_time' => $timestamp,
                ':prefix' => $this->getMessagePrefix($message),
                ':message' => $text,
            ])->execute();
        }*/
   // 	$fp=fopen("D://1551.txt","w");
    //	fwrite($fp,"fff444ff");
    	//fclose($fp);
    	 
    	$sysLogDB=\Yii::$app->subdb;
    	$sysLogSql = "INSERT INTO ut_sys_log ([[create_time]],[[module]], [[class]], [[function]], [[level]], [[remark]])
    	VALUES (:create_time,:module, :class, :function, :level, :remark)";
    	$sysLogCommand = $sysLogDB->createCommand($sysLogSql);
    	
    	$globalLogDB=\Yii::$app->db;
    	$globalLogSql = "INSERT INTO ut_global_log ([[create_time]],[[module]], [[class]], [[function]], [[level]],[[job_type]], [[remark]])
    	VALUES (:create_time,:module, :class, :function, :level, :job_type,:remark)";
    	$globalLogCommand = $globalLogDB->createCommand($globalLogSql);
    	 
  	
    	
    	foreach ($this->messages as $message) {
    		list($text, $level, $category, $timestamp) = $message;
    		//if (!is_string($text)) {
    		//	$text = VarDumper::export($text);
    		//}
    		$levelMap=array(1=>"error",2=>"warning",4=>"info",8=>"trace");
    		
    	    		
    		if ($category=="edb\user"){
    			list($module,$class,$function,$remark)=$text;
	    	/*	$module=$text[0];
	    		$class=$text[1];
	    		$function=$text[2];
	    		$remark=$text[3];*/
	
	    		$tempArr=explode('\\',$class);
	    		if (count($tempArr)>1)	$class=$tempArr[count($tempArr)-1];
	    				
	    	
	    		$sysLogCommand->bindValues([
	    				':create_time' => date("Y-m-d H:i:s",$timestamp),
	    				':module' => $module,
	    				':class' => $class,
	    				':function' => $function,
	    				':remark' => $remark,
	    				':level'=>$levelMap[$level]
	    				])->execute();
    		} else if ($category=="edb\global"){
    			$job_type="Online";
    			
    			if (count($text)==4)	list($module,$class,$function,$remark)=$text;
    			else list($module,$class,$function,$job_type,$remark)=$text;
    			
    		
    			$tempArr=explode('\\',$class);
    			$class=$tempArr[count($tempArr)-1];
    			 
    		
    			$globalLogCommand->bindValues([
    					':create_time' => date("Y-m-d H:i:s",$timestamp),
    					':module' => $module,
    					':class' => $class,
    					':function' => $function,
    					':job_type' => $job_type,    					
    					':remark' => $remark,    					
    					':level'=>$levelMap[$level]
    					
    					])->execute();
    		}     		
    	}
    	 
    }
}
