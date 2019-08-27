<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "sys_shipping_method".
 *
 * @property string $carrier_code
 * @property string $shipping_method_code
 * @property string $shipping_method_name
 * @property string $create_time
 * @property string $update_time
 * @property string $third_party_code
 * @property string $template
 */
class SysShippingMethod extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_shipping_method';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['carrier_code', 'shipping_method_code'], 'required'],
            [['create_time', 'update_time'], 'integer'],
            [['carrier_code'], 'string', 'max' => 50],
            [['shipping_method_name', 'third_party_code', 'template', 'shipping_method_code'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'carrier_code' => '物流商代码',
            'shipping_method_code' => '物流方式代码',
            'shipping_method_name' => '物流方式名',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'third_party_code' => '第三方物流方式代码',
            'template' => '模板名称',
        ];
    }
}
