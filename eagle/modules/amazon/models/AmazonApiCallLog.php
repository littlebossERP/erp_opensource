<?php

namespace eagle\modules\amazon\models;

use Yii;

/**
 * This is the model class for table "amazon_api_call_log".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $merchant_id
 * @property string $call_type
 * @property string $date
 * @property integer $count
 */
class AmazonApiCallLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_api_call_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'merchant_id', 'call_type', 'date', 'count'], 'required'],
            [['puid', 'count'], 'integer'],
            [['date'], 'safe'],
            [['merchant_id', 'call_type'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'merchant_id' => 'Merchant ID',
            'call_type' => 'Call Type',
            'date' => 'Date',
            'count' => 'Count',
        ];
    }
}
