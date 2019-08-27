<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "od_order_systags_mapping".
 *
 * @property integer $id
 * @property string $tag_code
 * @property integer $order_id
 */
class OrderSystagsMapping extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_systags_mapping';
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
            [['tag_code', 'order_id'], 'required'],
            [['order_id'], 'integer'],
            [['tag_code'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag_code' => 'Tag Code',
            'order_id' => 'Order ID',
        ];
    }
}
