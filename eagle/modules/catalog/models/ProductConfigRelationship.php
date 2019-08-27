<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_config_relationship".
 *
 * @property integer $id
 * @property string $cfsku
 * @property string $assku
 * @property string $config_field_ids
 * @property string $create_date
 */
class ProductConfigRelationship extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_config_relationship';
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
            [['cfsku', 'assku', 'config_field_ids'], 'required'],
            [['config_field_ids'], 'string'],
            [['create_date'], 'safe'],
            [['cfsku', 'assku'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cfsku' => 'Cfsku',
            'assku' => 'Assku',
            'config_field_ids' => 'Config Field Ids',
            'create_date' => 'Create Date',
        ];
    }
}
