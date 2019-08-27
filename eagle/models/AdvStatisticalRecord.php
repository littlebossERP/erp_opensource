<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "adv_statistical_record".
 *
 * @property integer $id
 * @property string $adv_code
 * @property string $adv_url
 * @property string $click_date
 * @property integer $click_count
 */
class AdvStatisticalRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'adv_statistical_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['adv_url'], 'string'],
            [['click_date'], 'safe'],
            [['click_count'], 'integer'],
            [['adv_code'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'adv_code' => 'Adv Code',
            'adv_url' => 'Adv Url',
            'click_date' => 'Click Date',
            'click_count' => 'Click Count',
        ];
    }
}
