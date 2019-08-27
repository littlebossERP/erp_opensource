<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_field".
 *
 * @property integer $id
 * @property string $field_name
 * @property string $field_name_eng
 * @property string $field_name_frc
 * @property string $field_name_ger
 * @property integer $use_freq
 */
class ProductField extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_field';
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
            [['field_name', 'field_name_eng', 'field_name_frc', 'field_name_ger'], 'required'],
            [['use_freq'], 'integer'],
            [['field_name', 'field_name_eng', 'field_name_frc', 'field_name_ger'], 'string', 'max' => 245],
            [['field_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'field_name' => 'Field Name',
            'field_name_eng' => 'Field Name Eng',
            'field_name_frc' => 'Field Name Frc',
            'field_name_ger' => 'Field Name Ger',
            'use_freq' => 'Use Freq',
        ];
    }
}
