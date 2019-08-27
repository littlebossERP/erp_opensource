<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "manual_sync".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $update_time
 * @property string $status
 * @property string $site_id
 * @property string $platform
 * @property string $type
 * @property integer $total
 * @property string $_data
 */
class ManualSync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'manual_sync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'safe'],
            [['total'], 'integer'],
            [['_data'], 'string'],
            [['status'], 'string', 'max' => 1],
            [['site_id'], 'string', 'max' => 255],
            [['platform', 'type'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'site_id' => 'Site ID',
            'platform' => 'Platform',
            'type' => 'Type',
            'total' => 'Total',
            '_data' => 'Data',
        ];
    }
}
