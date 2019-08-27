<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "package_bonus_month".
 *
 * @property string $id
 * @property string $app_name
 * @property string $year_month
 */
class PackageBonusMonth extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'package_bonus_month';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['year_month'], 'safe'],
            [['app_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_name' => 'App Name',
            'year_month' => 'Year Month',
        ];
    }
}
