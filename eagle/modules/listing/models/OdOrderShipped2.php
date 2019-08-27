<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "od_order_shipped".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $order_source
 * @property string $selleruserid
 * @property string $tracking_number
 * @property string $tracking_link
 * @property string $shipping_method_code
 * @property integer $status
 * @property string $sync_to_tracker
 * @property string $result
 * @property string $errors
 * @property integer $created
 * @property integer $lasttime
 */
class OdOrderShipped2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_shipped';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['order_id', 'status', 'created', 'lasttime'], 'integer'],
            [['order_source', 'tracking_number', 'shipping_method_code'], 'string', 'max' => 50],
            [['selleruserid'], 'string', 'max' => 80],
            [['tracking_link'], 'string', 'max' => 100],
            [['sync_to_tracker'], 'string', 'max' => 1],
            [['result'], 'string', 'max' => 20],
            [['errors'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'order_source' => 'Order Source',
            'selleruserid' => 'Selleruserid',
            'tracking_number' => 'Tracking Number',
            'tracking_link' => 'Tracking Link',
            'shipping_method_code' => 'Shipping Method Code',
            'status' => 'Status',
            'sync_to_tracker' => 'Sync To Tracker',
            'result' => 'Result',
            'errors' => 'Errors',
            'created' => 'Created',
            'lasttime' => 'Lasttime',
        ];
    }
}
