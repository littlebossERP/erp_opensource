<?php

namespace eagle\modules\purchase\models;

use Yii;

/**
 * This is the model class for table "pc_1688_listing".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $company_name
 * @property string $product_id
 * @property string $sku_1688
 * @property string $spec_id
 * @property string $name
 * @property string $image_url
 * @property string $pro_link
 * @property string $attributes
 * @property string $sku
 */
class Pc1688Listing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pc_1688_listing';
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
            [['user_id', 'product_id', 'sku_1688', 'spec_id', 'name', 'sku'], 'required'],
            [['user_id'], 'integer'],
            [['company_name', 'sku_1688', 'spec_id', 'name', 'image_url', 'pro_link', 'attributes', 'sku'], 'string', 'max' => 255],
            [['product_id'], 'string', 'max' => 50],
            [['product_id', 'sku_1688'], 'unique', 'targetAttribute' => ['product_id', 'sku_1688'], 'message' => 'The combination of Product ID and Sku 1688 has already been taken.'],
            [['user_id', 'sku'], 'unique', 'targetAttribute' => ['user_id', 'sku'], 'message' => 'The combination of User ID and Sku has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'company_name' => 'Company Name',
            'product_id' => 'Product ID',
            'sku_1688' => 'Sku 1688',
            'spec_id' => 'Spec ID',
            'name' => 'Name',
            'image_url' => 'Image Url',
            'pro_link' => 'Pro Link',
            'attributes' => 'Attributes',
            'sku' => 'Sku',
        ];
    }
}
