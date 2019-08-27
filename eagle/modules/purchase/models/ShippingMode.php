<?php

namespace eagle\modules\purchase\models;

use Yii;

/**
 * This is the model class for table "pc_shipping_mode".
 *
 * @property integer $shipping_id
 * @property string $shipping_name
 * @property string $comment
 * @property string $addi_info
 * @property string $create_time
 * @property integer $capture_user
 */
class ShippingMode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pc_shipping_mode';
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
            [['create_time'], 'safe'],
            [['capture_user'], 'required'],
            [['capture_user'], 'integer'],
            [['shipping_name'], 'string', 'max' => 100],
            [['comment', 'addi_info'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shipping_id' => 'Shipping ID',
            'shipping_name' => 'Shipping Name',
            'comment' => 'Comment',
            'addi_info' => 'Addi Info',
            'create_time' => 'Create Time',
            'capture_user' => 'Capture User',
        ];
    }
}
