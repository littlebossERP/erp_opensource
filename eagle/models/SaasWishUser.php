<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_wish_user".
 *
 * @property string $site_id
 * @property string $token
 * @property string $store_name
 * @property integer $is_active
 * @property string $uid
 * @property string $create_time
 * @property string $update_time
 * @property string $last_order_retrieve_time
 * @property string $last_order_success_retrieve_time
 * @property string $order_retrieve_message
 * @property string $last_product_retrieve_time
 * @property string $last_product_success_retrieve_time
 * @property string $product_retrieve_message
 * @property string $addi_info
 * @property string $routine_fetched_changed_order_from
 * @property string $initial_fetched_changed_order_since
 * @property string $merchant_id
 * @property integer $expires_in
 * @property string $expiry_time
 * @property string $refresh_token
 * @property string $client_id
 * @property string $client_secret
 * @property string $code
 * @property string $redirect_uri
 * @property string $order_manual_retrieve
 * @property string $last_order_manual_retrieve_time
 * @property string $order_manual_retrieve_message
 * @property string $oq_status
 * @property string $oq_lockedby
 */
class SaasWishUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_wish_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_active', 'uid', 'expires_in'], 'integer'],
            [['create_time'], 'required'],
            [['create_time', 'update_time', 'last_order_retrieve_time', 'last_order_success_retrieve_time', 'last_product_retrieve_time', 'last_product_success_retrieve_time', 'routine_fetched_changed_order_from', 'initial_fetched_changed_order_since', 'expiry_time', 'last_order_manual_retrieve_time'], 'safe'],
            [['order_retrieve_message', 'product_retrieve_message', 'addi_info', 'refresh_token', 'redirect_uri', 'order_manual_retrieve_message'], 'string'],
            [['token'], 'string', 'max' => 250],
            [['store_name'], 'string', 'max' => 50],
            [['merchant_id'], 'string', 'max' => 100],
            [['client_id', 'client_secret', 'code'], 'string', 'max' => 255],
            [['order_manual_retrieve', 'oq_status'], 'string', 'max' => 1],
            [['oq_lockedby'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'site_id' => 'Site ID',
            'token' => 'Token',
            'store_name' => 'Store Name',
            'is_active' => 'Is Active',
            'uid' => 'Uid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_order_retrieve_time' => 'Last Order Retrieve Time',
            'last_order_success_retrieve_time' => 'Last Order Success Retrieve Time',
            'order_retrieve_message' => 'Order Retrieve Message',
            'last_product_retrieve_time' => 'Last Product Retrieve Time',
            'last_product_success_retrieve_time' => 'Last Product Success Retrieve Time',
            'product_retrieve_message' => 'Product Retrieve Message',
            'addi_info' => 'Addi Info',
            'routine_fetched_changed_order_from' => 'Routine Fetched Changed Order From',
            'initial_fetched_changed_order_since' => 'Initial Fetched Changed Order Since',
            'merchant_id' => 'Merchant ID',
            'expires_in' => 'Expires In',
            'expiry_time' => 'Expiry Time',
            'refresh_token' => 'Refresh Token',
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'code' => 'Code',
            'redirect_uri' => 'Redirect Uri',
            'order_manual_retrieve' => 'Order Manual Retrieve',
            'last_order_manual_retrieve_time' => 'Last Order Manual Retrieve Time',
            'order_manual_retrieve_message' => 'Order Manual Retrieve Message',
            'oq_status' => 'Oq Status',
            'oq_lockedby' => 'Oq Lockedby',
        ];
    }
}
