<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "package_bonus_record".
 *
 * @property string $id
 * @property integer $puid
 * @property string $year_month
 * @property string $app_name
 * @property string $surplus_value
 * @property string $bonus_value
 * @property integer $package_open_time
 */
class PackageBonusRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'package_bonus_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'package_open_time'], 'integer'],
            [['surplus_value', 'bonus_value'], 'number'],
            [['year_month', 'app_name'], 'string', 'max' => 255]
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
            'year_month' => 'Year Month',
            'app_name' => 'App Name',
            'surplus_value' => 'Surplus Value',
            'bonus_value' => 'Bonus Value',
            'package_open_time' => 'Package Open Time',
        ];
    }
}
