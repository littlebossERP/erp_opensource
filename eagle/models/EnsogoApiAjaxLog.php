<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%ensogo_api_ajax_log}}".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $product_id
 * @property string $variation_id
 * @property string $request_id
 * @property string $error_message
 * @property integer $create_time
 * @property integer $update_time
 */
class EnsogoApiAjaxLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ensogo_api_ajax_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'create_time', 'update_time'], 'integer'],
            [['error_message'], 'required'],
            [['error_message'], 'string'],
            [['product_id', 'variation_id', 'request_id'], 'string', 'max' => 255]
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
            'variation_id' => 'Variation ID',
            'request_id' => 'Request ID',
            'error_message' => 'Error Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
