<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "order_history_statistics_data".
 *
 * @property string $id
 * @property string $type
 * @property string $history_date
 * @property string $json_params
 */
class OrderHistoryStatisticsData extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_history_statistics_data';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['json_params'], 'string'],
            [['type', 'history_date'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'history_date' => 'History Date',
            'json_params' => 'Json Params',
        ];
    }
}
