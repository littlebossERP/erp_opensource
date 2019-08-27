<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_attributes".
 *
 * @property integer $attribute_id
 * @property string $name
 * @property integer $use_count
 * @property string $values
 */
class Attributes extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_attributes';
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
            [['use_count'], 'required'],
            [['use_count'], 'integer'],
            [['name'], 'string', 'max' => 45],
            [['values'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'attribute_id' => 'Attribute ID',
            'name' => 'Name',
            'use_count' => 'Use Count',
            'values' => 'Values',
        ];
    }
}
