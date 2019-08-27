<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_aliases".
 *
 * @property integer $product_alias_id
 * @property string $sku
 * @property string $alias_sku
 * @property integer $pack
 * @property string $forsite
 * @property string $comment
 */
class ProductAliases extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_aliases';
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
            [['pack'], 'integer'],
            [['comment'], 'string'],
            [['sku', 'alias_sku'], 'string', 'max' => 255],
            [['forsite'], 'string', 'max' => 50],
            [['alias_sku', 'platform', 'selleruserid'], 'unique', 'targetAttribute' => ['alias_sku', 'platform', 'selleruserid'], 'message' => 'The combination of Alias Sku and platform and shopname has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_alias_id' => 'Product Alias ID',
            'sku' => 'Sku',
            'alias_sku' => 'Alias Sku',
            'pack' => 'Pack',
            'forsite' => 'Forsite',
            'comment' => 'Comment',
        ];
    }
}
