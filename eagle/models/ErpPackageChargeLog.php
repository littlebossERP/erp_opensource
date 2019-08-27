<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "erp_package_charge_log".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $charge_data_bef
 * @property string $update_time
 */
class ErpPackageChargeLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'erp_package_charge_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid'], 'integer'],
            [['charge_data_bef'], 'string'],
            [['update_time'], 'safe']
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
            'charge_data_bef' => 'Charge Data Bef',
            'update_time' => 'Update Time',
        ];
    }
}
