<?php

namespace eagle\models\order;

use Yii;

/**
 * This is the model class for table "od_order_relation".
 *
 * @property integer $id
 * @property integer $father_orderid
 * @property integer $son_orderid
 * @property string $type
 */
class OrderRelation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_relation';
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
            [['father_orderid', 'son_orderid'], 'required'],
            [['father_orderid', 'son_orderid'], 'integer'],
            [['type'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'father_orderid' => 'Father Orderid',
            'son_orderid' => 'Son Orderid',
            'type' => 'Type',
        ];
    }
}
