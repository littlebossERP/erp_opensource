<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "erp_vip_custom_package".
 *
 * @property string $id
 * @property integer $puid
 * @property string $erp_level_version
 * @property string $erp_permissions_data
 * @property string $package_price
 * @property integer $pay_cycle
 * @property integer $is_use
 * @property integer $create_time
 * @property integer $update_time
 */
class ErpVipCustomPackage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'erp_vip_custom_package';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'pay_cycle', 'is_use', 'create_time', 'update_time'], 'integer'],
            [['erp_permissions_data'], 'string'],
            [['package_price'], 'number'],
            [['erp_level_version'], 'string', 'max' => 5]
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
            'erp_level_version' => 'Erp Level Version',
            'erp_permissions_data' => 'Erp Permissions Data',
            'package_price' => 'Package Price',
            'pay_cycle' => 'Pay Cycle',
            'is_use' => 'Is Use',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
