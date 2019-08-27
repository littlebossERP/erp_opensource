<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_getorder".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property integer $aliexpress_uid
 * @property integer $status
 * @property string $order_status
 * @property string $orderid
 * @property integer $times
 * @property string $order_info
 * @property integer $last_time
 * @property integer $gmtcreate
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 */
class QueueCrawl extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'queue_crawl';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue');
    }
}
