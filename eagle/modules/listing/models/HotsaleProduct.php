<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "cdot_followed_product".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $product_id
 * @property string $ean
 * @property string $create_time
 * @property string $update_time
 * @property string $last_sync_time
 * @property string $last_success_sync_time
 * @property string $error_message
 * @property string $add_info
 */
class HotsaleProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cdot_hotsale_product';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'product_id', 'create_time', 'update_time', 'last_sync_time', 'last_success_sync_time', 'error_message', 'add_info'], 'required'],
            [['puid'], 'integer'],
            [['create_time', 'update_time', 'last_sync_time', 'last_success_sync_time'], 'safe'],
            [['error_message', 'add_info'], 'string'],
            [['product_id'], 'string', 'max' => 50],
            [['ean'], 'string', 'max' => 13],
            [['product_id'], 'unique']
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
            'product_id' => 'Product ID',
            'ean' => 'Ean',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_sync_time' => 'Last Sync Time',
            'last_success_sync_time' => 'Last Success Sync Time',
            'error_message' => 'Error Message',
            'add_info' => 'Add Info',
        ];
    }
}
