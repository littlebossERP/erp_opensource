<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_getorder".
 *
 *
 */
class QueueIpList extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_ip_list';
    }
    
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue');
    }

}
