<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_rumall_user".
 *
 * @property string $site_id
 * @property string $token
 * @property string $store_name
 * @property string $company_code
 * @property string $token_expired_date
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
 * @property string $initial_fetched_changed_order_since
 * @property string $addi_info
 * @property string $routine_fetched_changed_order_from
 */
class SaasRumallUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_rumall_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['token_expired_date', 'create_time', 'update_time', 'last_order_retrieve_time', 'last_order_success_retrieve_time', 'last_product_retrieve_time', 'last_product_success_retrieve_time', 'initial_fetched_changed_order_since', 'routine_fetched_changed_order_from'], 'safe'],
            [['is_active', 'uid'], 'integer'],
            [['create_time'], 'required'],
            [['order_retrieve_message', 'product_retrieve_message', 'addi_info'], 'string'],
            [['token'], 'string', 'max' => 250],
            [['store_name'], 'string', 'max' => 50],
            [['company_code'], 'string', 'max' => 255]
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
            'company_code' => 'Company Code',
            'token_expired_date' => 'Token Expired Date',
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
            'initial_fetched_changed_order_since' => 'Initial Fetched Changed Order Since',
            'addi_info' => 'Addi Info',
            'routine_fetched_changed_order_from' => 'Routine Fetched Changed Order From',
        ];
    }
}
