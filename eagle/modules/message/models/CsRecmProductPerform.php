<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_recm_product_perform".
 *
 * @property integer $id
 * @property integer $product_id
 * @property string $theday
 * @property integer $view_count
 * @property integer $click_count
 */
class CsRecmProductPerform extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_recm_product_perform';
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
            [['product_id', 'view_count', 'click_count'], 'integer'],
            [['theday'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'theday' => 'Theday',
            'view_count' => 'View Count',
            'click_count' => 'Click Count',
        ];
    }
}
