<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "sys_carrier_custom".
 *
 * @property integer $id
 * @property string $carrier_name
 * @property integer $carrier_type
 * @property string $address_list
 * @property integer $create_time
 * @property integer $update_time
 */
class SysCarrierCustom extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_carrier_custom';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['carrier_name'], 'required'],
            [['carrier_type', 'create_time', 'update_time'], 'integer'],
            [['carrier_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'carrier_code' => '物流商代码',
            'carrier_name' => '自定义物流商名称',
            'carrier_type' => '字段暂时没有用，预留，物流商类型',
            'address_list' => '地址列表',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
