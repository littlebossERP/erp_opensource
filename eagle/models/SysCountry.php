<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "sys_country".
 *
 * @property string $country_code
 * @property string $country_en
 * @property string $country_zh
 * @property string $region
 * @property string $create_time
 * @property string $update_time
 */
class SysCountry extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_country';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['country_code'], 'required'],
            [['create_time', 'update_time'], 'integer'],
            [['country_code'], 'string', 'max' => 2],
            [['country_en', 'country_zh', 'region'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'country_code' => 'Country Code',
            'country_en' => 'Country En',
            'country_zh' => 'Country Zh',
            'region' => 'Region',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
