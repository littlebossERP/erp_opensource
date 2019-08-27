<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%ensogo_store_move_log}}".
 *
 * @property integer $id
 * @property string $platform
 * @property integer $puid
 * @property string $store_id
 * @property integer $move_number
 * @property integer $success_number
 * @property string $error_message
 * @property string $error_api_message
 * @property string $shipping_time
 * @property string $category_id
 * @property string $wish_product_id
 * @property integer $create_time
 * @property integer $update_time
 */
class EnsogoStoreMoveLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ensogo_store_move_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'move_number', 'success_number', 'create_time', 'update_time','status'], 'integer'],
            [['error_message', 'error_api_message', 'wish_product_id'], 'required'],
            [['error_message', 'error_api_message', 'wish_product_id','ajax_fail_id'], 'string'],
            [['platform', 'store_id', 'shipping_time', 'category_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'puid' => 'Puid',
            'store_id' => 'Store ID',
            'move_number' => 'Move Number',
            'success_number' => 'Success Number',
            'error_message' => 'Error Message',
            'error_api_message' => 'Error Api Message',
            'shipping_time' => 'Shipping Time',
            'category_id' => 'Category ID',
            'wish_product_id' => 'Wish Product ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' =>'status',
            'ajax_fail_id' => 'ajax_fail_id'
        ];
    }
}
