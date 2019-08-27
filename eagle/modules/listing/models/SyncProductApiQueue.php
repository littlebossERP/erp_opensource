<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "sync_product_api_queue".
 *
 * @property integer $id
 * @property string $status
 * @property integer $priority
 * @property integer $puid
 * @property string $seller_id
 * @property string $create_time
 * @property string $update_time
 * @property string $platform
 * @property integer $run_time
 * @property string $addi_info
 */
class SyncProductApiQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sync_product_api_queue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'priority', 'seller_id', 'create_time', 'update_time', 'platform'], 'required'],
            [['priority', 'puid', 'run_time'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['platform', 'addi_info'], 'string'],
            [['status'], 'string', 'max' => 1],
            [['seller_id'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status' => 'Status',
            'priority' => 'Priority',
            'puid' => 'Puid',
            'seller_id' => 'Seller ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'platform' => 'Platform',
            'run_time' => 'Run Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
