<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "sys_shipping_code_name_map".
 *
 * @property integer $id
 * @property string $platform
 * @property string $shipping_code
 * @property string $shipping_name
 * @property string $create_time
 * @property string $update_time
 */
class SysShippingCodeNameMap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_shipping_code_name_map';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time'], 'integer'],
            [['platform'], 'string', 'max' => 50],
            [['shipping_code'], 'string', 'max' => 100],
            [['shipping_name'], 'string', 'max' => 150]
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
            'shipping_code' => 'Shipping Code',
            'shipping_name' => 'Shipping Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
