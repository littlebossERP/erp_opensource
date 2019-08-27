<?php

namespace eagle\modules\util\helpers;

use yii;
use yii\data\Pagination;

use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\StringHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\apihelpers\MessageAliexpressApiHelper;
use eagle\modules\message\models\Message;
use eagle\modules\message\apihelpers\MessageEbayApiHelper;
use yii\base\Exception;

/**
 * +------------------------------------------------------------------------------
 * config模块---- 读取各个模块配置的参数数值
 * +------------------------------------------------------------------------------
 * @package        Helper
 * @subpackage  Exception
 * @author        xjq
 * @version        1.0
 * +------------------------------------------------------------------------------
 */
class SQLHelper
{

    static public function groupInsertToDb($tableName, $Datas, $db = "subdb", $update_fields = array())
    {
        //use sql PDO, not Model here, for best performance
        $totalInserted = 0;
        $fields_str = '';
        $sql_values = '';
        $fields = array();
        //step 1, create a full SQL.
        $i = 10000;
        $starti = $i + 1;
        $bindDatas = array();
        foreach ($Datas as $data) {
            $i++;

            $bindDatas[$i] = $data;
            $eachRecordValues = '';

            //找到这个表要insert 的所有field 字段名
            if ($fields_str == '') {
                foreach ($data as $fieldName => $fieldValue) {

                    if (!empty($update_fields) and empty($update_fields[$fieldName]))
                        continue;

                    $fields_str .= empty($fields_str) ? "" : ",";
                    $fields_str .= $fieldName;
                    $fields[] = $fieldName;
                }
                $sql = " INSERT INTO  `$tableName` ( $fields_str ) VALUES ";
            }

            //值的排列
            foreach ($data as $fieldName => $fieldValue) {
                if (!empty($update_fields) and empty($update_fields[$fieldName]))
                    continue;

                $eachRecordValues .= empty($eachRecordValues) ? "" : ",";
                $eachRecordValues .= ":" . $fieldName . $i;
            }

            $sql_values .= ($sql_values == '' ? '' : ",") . "( $eachRecordValues )";

            if (strlen($sql_values) > 2000) {
                //one sql syntax do not exceed 4800, so make 3000 as a cut here

                $command = Yii::$app->get($db)->createCommand($sql . $sql_values . ";");

                //bind all values
                for ($tempi = $starti; $tempi <= $i; $tempi++) {
                    foreach ($fields as $aField) {
                        $command->bindValue(':' . $aField . $tempi, $bindDatas[$tempi][$aField], \PDO::PARAM_STR);
                    }
                }//end of each data index for this bulk insert

                $totalInserted += $command->execute();

                $sql_values = '';
                $starti = $i + 1;
            }
        }//end of each track no

        //step 2, insert the rest
        if ($sql_values <> '') {
            if ($db == 'subdb')
                $command = Yii::$app->subdb->createCommand($sql . $sql_values . ";");
            elseif ($db == 'ost_db')
                $command = Yii::$app->ost_db->createCommand($sql . $sql_values . ";");
            elseif ($db == 'db_queue')
                $command = Yii::$app->db_queue->createCommand($sql . $sql_values . ";");
            elseif ($db == 'db_queue2')
                $command = Yii::$app->db_queue2->createCommand($sql . $sql_values . ";");
            else
                $command = Yii::$app->db->createCommand($sql . $sql_values . ";");

            for ($tempi = $starti; $tempi <= $i; $tempi++) {
                foreach ($fields as $aField) {
                    $command->bindValue(':' . $aField . $tempi, $bindDatas[$tempi][$aField], \PDO::PARAM_STR);
                }
            }//end of each data index for this bulk insert
            $totalInserted += $command->execute();
        }

        return $totalInserted;
    }//end of function postTrackingBufferToDb


}