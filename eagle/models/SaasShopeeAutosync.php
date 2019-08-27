<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_shopee_autosync".
 *
 * @property string $id
 * @property integer $puid
 * @property integer $shopee_uid
 * @property string $shop_id
 * @property string $site
 * @property integer $is_active
 * @property integer $status
 * @property string $type
 * @property integer $times
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $last_time
 * @property integer $binding_time
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $next_time
 * @property integer $order_item
 */
class SaasShopeeAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_shopee_autosync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'shopee_uid', 'shop_id', 'status', 'times'], 'required'],
            [['puid', 'shopee_uid', 'is_active', 'status', 'times', 'start_time', 'end_time', 'last_time', 'binding_time', 'create_time', 'update_time', 'next_time', 'order_item'], 'integer'],
            [['message'], 'string'],
            [['shop_id', 'type'], 'string', 'max' => 50],
            [['site'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'shopee_uid' => 'Shopee Uid',
            'shop_id' => 'Shop ID',
            'site' => 'Site',
            'is_active' => 'Is Active',
            'status' => 'Status',
            'type' => 'Type',
            'times' => 'Times',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'last_time' => 'Last Time',
            'binding_time' => 'Binding Time',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'next_time' => 'Next Time',
            'order_item' => 'Order Item',
        ];
    }
}
