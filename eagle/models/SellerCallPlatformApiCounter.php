<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "seller_call_platform_api_counter".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $platform
 * @property string $merchant_id
 * @property string $call_type
 * @property string $datetime
 * @property integer $count
 */
class SellerCallPlatformApiCounter extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'seller_call_platform_api_counter';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'platform', 'merchant_id', 'call_type', 'datetime', 'count'], 'required'],
            [['puid', 'count'], 'integer'],
            [['datetime'], 'safe'],
            [['platform', 'merchant_id', 'call_type'], 'string', 'max' => 50]
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
            'platform' => 'Platform',
            'merchant_id' => 'Merchant ID',
            'call_type' => 'Call Type',
            'datetime' => 'Datetime',
            'count' => 'Count',
        ];
    }
}
