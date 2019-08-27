<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_shopee_user".
 *
 * @property string $shopee_uid
 * @property string $store_name
 * @property string $puid
 * @property string $shop_id
 * @property string $partner_id
 * @property string $secret_key
 * @property string $site
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * @property string $addi_info
 */
class SaasShopeeUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_shopee_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'shop_id', 'partner_id', 'secret_key', 'site', 'create_time', 'update_time'], 'required'],
            [['puid', 'status', 'create_time', 'update_time'], 'integer'],
            [['addi_info'], 'string'],
            [['store_name', 'shop_id', 'partner_id'], 'string', 'max' => 50],
            [['secret_key'], 'string', 'max' => 255],
            [['site'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shopee_uid' => 'Shopee Uid',
            'store_name' => 'Store Name',
            'puid' => 'Puid',
            'shop_id' => 'Shop ID',
            'partner_id' => 'Partner ID',
            'secret_key' => 'Secret Key',
            'site' => 'Site',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
