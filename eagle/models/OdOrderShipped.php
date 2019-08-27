<?php

namespace eagle\models;

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
 * @property string $result
 * @property string $errors
 * @property integer $created
 * @property integer $lasttime
 * @property string $returnNo
 * @property integer $shipping_service_id
 */
class OdOrderShipped extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_shipped_v2';
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
            [['order_id', 'status', 'created', 'lasttime', 'shipping_service_id'], 'integer'],
            [['order_source', 'tracking_number', 'shipping_method_code'], 'string', 'max' => 50],
			
            [['selleruserid'], 'string', 'max' => 80],
            [['tracking_link'], 'string'],
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
            'result' => 'Result',
            'errors' => 'Errors',
            'created' => 'Created',
            'lasttime' => 'Lasttime',
            'return_no' => 'Return No',
            'shipping_service_id' => 'Shipping Service ID',
        ];
    }
}
