<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_bundle_relationship".
 *
 * @property integer $id
 * @property string $bdsku
 * @property string $assku
 * @property integer $qty
 * @property string $create_date
 */
class ProductBundleRelationship extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_bundle_relationship';
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
            [['bdsku', 'assku', 'qty', 'create_date'], 'required'],
            [['qty'], 'integer'],
            [['create_date'], 'safe'],
            [['bdsku', 'assku'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bdsku' => 'Bdsku',
            'assku' => 'Assku',
            'qty' => 'Qty',
            'create_date' => 'Create Date',
        ];
    }
}
