<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_getorder".
 *
 * @property string $id
 * @property string $selleruserid
 * @property string $ebay_uid
 * @property string $ebay_orderid
 * @property string $paypalemailadress
 * @property integer $status
 * @property string $created
 * @property string $updated
 */
class QueueGetorder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_getorder';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ebay_uid', 'status', 'created', 'updated'], 'integer'],
            [['selleruserid', 'ebay_orderid', 'paypalemailadress'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'selleruserid' => 'Selleruserid',
            'ebay_uid' => 'Ebay Uid',
            'ebay_orderid' => 'Ebay Orderid',
            'paypalemailadress' => 'Paypalemailadress',
            'status' => 'Status',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
