<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_customer_tags".
 *
 * @property integer $customer_tag_id
 * @property string $customer_id
 * @property string $tag_id
 */
class CustomerTags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_customer_tags';
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
            [['tag_id'], 'integer'],
            [['customer_id'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_tag_id' => 'Customer Tag ID',
            'customer_id' => 'Customer ID',
            'tag_id' => 'Tag ID',
        ];
    }
}
