<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_warehouse".
 *
 * @property integer $warehouse_id
 * @property string $name
 * @property string $is_active
 * @property string $address_nation
 * @property string $address_state
 * @property string $address_city
 * @property string $address_street
 * @property string $address_postcode
 * @property string $address_phone
 * @property string $comment
 * @property string $addi_info
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $is_oversea
 */
class Warehouse extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_warehouse';
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
            [['capture_user_id', 'is_oversea'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['is_active'], 'string', 'max' => 1],
            [['address_nation'], 'string', 'max' => 2],
            [['address_state', 'address_city'], 'string', 'max' => 100],
            [['address_street', 'comment', 'addi_info'], 'string', 'max' => 255],
            [['address_postcode', 'address_phone'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'warehouse_id' => 'Warehouse ID',
            'name' => 'Name',
            'is_active' => 'Is Active',
            'address_nation' => 'Address Nation',
            'address_state' => 'Address State',
            'address_city' => 'Address City',
            'address_street' => 'Address Street',
            'address_postcode' => 'Address Postcode',
            'address_phone' => 'Address Phone',
            'comment' => 'Comment',
            'addi_info' => 'Addi Info',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_oversea' => 'Is Oversea',
        ];
    }
}
