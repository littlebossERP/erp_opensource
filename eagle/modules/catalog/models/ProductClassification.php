<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_classification".
 *
 * @property integer $ID
 * @property string $number
 * @property string $name
 * @property string $parent_number
 */
class ProductClassification extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_classification';
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
            [['name'], 'required'],
            [['number', 'parent_number'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'number' => 'Number',
            'name' => 'Name',
            'parent_number' => 'Parent Number',
        ];
    }
}
