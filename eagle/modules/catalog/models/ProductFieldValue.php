<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_field_value".
 *
 * @property integer $id
 * @property integer $field_id
 * @property string $value
 * @property integer $use_freq
 */
class ProductFieldValue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_field_value';
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
            [['field_id', 'use_freq'], 'integer'],
            [['value'], 'required'],
            [['value'], 'string', 'max' => 245]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'field_id' => 'Field ID',
            'value' => 'Value',
            'use_freq' => 'Use Freq',
        ];
    }
}
