<?php
namespace eagle\models;

use Yii;

class CheckSync extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 'check_sync';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }
}