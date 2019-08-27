<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_paypaltransaction".
 *
 * @property string $qid
 * @property integer $eorderid
 * @property string $ebay_orderid
 * @property string $selleruserid
 * @property string $externaltransactionid
 * @property integer $status
 * @property string $itemids
 * @property string $error
 * @property integer $created
 * @property integer $updated
 */
class QueuePaypaltransaction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_paypaltransaction';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['eorderid', 'status', 'created', 'updated'], 'integer'],
            [['updated'], 'required'],
            [['ebay_orderid', 'selleruserid'], 'string', 'max' => 50],
            [['externaltransactionid'], 'string', 'max' => 20],
            [['itemids', 'error'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'qid' => 'Qid',
            'eorderid' => 'Eorderid',
            'ebay_orderid' => 'Ebay Orderid',
            'selleruserid' => 'Selleruserid',
            'externaltransactionid' => 'Externaltransactionid',
            'status' => 'Status',
            'itemids' => 'Itemids',
            'error' => 'Error',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
