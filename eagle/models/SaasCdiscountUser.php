<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_cdiscount_user".
 *
 * @property string $site_id
 * @property string $sync_type
 * @property string $sync_status
 * @property string $sync_info
 * @property string $token
 * @property string $store_name
 * @property string $username
 * @property string $password
 * @property string $token_expired_date
 * @property string $api_username
 * @property string $api_password
 * @property integer $auth_type
 * @property integer $is_active
 * @property string $uid
 * @property string $create_time
 * @property string $update_time
 * @property string $last_order_retrieve_time
 * @property string $last_order_success_retrieve_time
 * @property string $order_retrieve_message
 * @property string $last_product_retrieve_time
 * @property string $last_product_success_retrieve_time
 * @property string $fetcht_offer_list_time
 * @property string $product_retrieve_message
 * @property string $initial_fetched_changed_order_since
 * @property string $addi_info
 * @property string $routine_fetched_changed_order_from
 * @property string $shopname
 * @property string $product_retrieve_error
 */
class SaasCdiscountUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_cdiscount_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sync_info', 'order_retrieve_message', 'product_retrieve_message', 'addi_info', 'product_retrieve_error'], 'string'],
            [['username', 'create_time'], 'required'],
            [['token_expired_date', 'create_time', 'update_time', 'last_order_retrieve_time', 'last_order_success_retrieve_time', 'last_product_retrieve_time', 'last_product_success_retrieve_time', 'fetcht_offer_list_time', 'initial_fetched_changed_order_since', 'routine_fetched_changed_order_from'], 'safe'],
            [['auth_type', 'is_active', 'uid'], 'integer'],
            [['sync_type', 'sync_status'], 'string', 'max' => 1],
            [['token'], 'string', 'max' => 250],
            [['store_name'], 'string', 'max' => 50],
            [['username', 'password', 'api_username', 'api_password', 'shopname'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'site_id' => 'Site ID',
            'sync_type' => 'Sync Type',
            'sync_status' => 'Sync Status',
            'sync_info' => 'Sync Info',
            'token' => 'Token',
            'store_name' => 'Store Name',
            'username' => 'Username',
            'password' => 'Password',
            'token_expired_date' => 'Token Expired Date',
            'api_username' => 'Api Username',
            'api_password' => 'Api Password',
            'auth_type' => 'Auth Type',
            'is_active' => 'Is Active',
            'uid' => 'Uid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_order_retrieve_time' => 'Last Order Retrieve Time',
            'last_order_success_retrieve_time' => 'Last Order Success Retrieve Time',
            'order_retrieve_message' => 'Order Retrieve Message',
            'last_product_retrieve_time' => 'Last Product Retrieve Time',
            'last_product_success_retrieve_time' => 'Last Product Success Retrieve Time',
            'fetcht_offer_list_time' => 'Fetcht Offer List Time',
            'product_retrieve_message' => 'Product Retrieve Message',
            'initial_fetched_changed_order_since' => 'Initial Fetched Changed Order Since',
            'addi_info' => 'Addi Info',
            'routine_fetched_changed_order_from' => 'Routine Fetched Changed Order From',
            'shopname' => 'Shopname',
            'product_retrieve_error' => 'Product Retrieve Error',
        ];
    }
}
