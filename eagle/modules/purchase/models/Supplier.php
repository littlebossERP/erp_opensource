<?php

namespace eagle\modules\purchase\models;

use Yii;

/**
 * This is the model class for table "pd_supplier".
 *
 * @property integer $supplier_id
 * @property string $name
 * @property string $address_nation
 * @property string $address_state
 * @property string $address_city
 * @property string $address_street
 * @property string $url
 * @property string $post_code
 * @property string $phone_number
 * @property string $fax_number
 * @property string $contact_name
 * @property string $mobile_number
 * @property string $qq
 * @property string $ali_wanwan
 * @property string $msn
 * @property string $email
 * @property integer $status
 * @property integer $account_settle_mode
 * @property string $payment_mode
 * @property string $payment_account
 * @property string $comment
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $is_disable
 */
class Supplier extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_supplier';
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
            [['url'], 'string'],
            [['status', 'account_settle_mode', 'capture_user_id', 'is_disable'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 30],
            [['address_nation'], 'string', 'max' => 2],
            [['address_state', 'address_city', 'phone_number', 'fax_number', 'mobile_number', 'qq', 'ali_wanwan', 'msn', 'email', 'payment_mode', 'payment_account'], 'string', 'max' => 100],
            [['address_street', 'comment'], 'string', 'max' => 255],
            [['post_code', 'contact_name'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'supplier_id' => 'Supplier ID',
            'name' => 'Name',
            'address_nation' => 'Address Nation',
            'address_state' => 'Address State',
            'address_city' => 'Address City',
            'address_street' => 'Address Street',
            'url' => 'Url',
            'post_code' => 'Post Code',
            'phone_number' => 'Phone Number',
            'fax_number' => 'Fax Number',
            'contact_name' => 'Contact Name',
            'mobile_number' => 'Mobile Number',
            'qq' => 'Qq',
            'ali_wanwan' => 'Ali Wanwan',
            'msn' => 'Msn',
            'email' => 'Email',
            'status' => 'Status',
            'account_settle_mode' => 'Account Settle Mode',
            'payment_mode' => 'Payment Mode',
            'payment_account' => 'Payment Account',
            'comment' => 'Comment',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_disable' => 'Is Disable',
        ];
    }
}
