<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "lt_order_tags".
 *
 * @property integer $tagid
 * @property integer $order_id
 * @property string $tag_id
 */
class LtOrderTags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_order_tags';
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
            [['order_id'], 'required'],
            [['order_id', 'tag_id'], 'integer'],
            [['order_id', 'tag_id'], 'unique', 'targetAttribute' => ['order_id', 'tag_id'], 'message' => 'The combination of Order ID and Tag ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tagid' => 'Tagid',
            'order_id' => 'Order ID',
            'tag_id' => 'Tag ID',
        ];
    }
}
