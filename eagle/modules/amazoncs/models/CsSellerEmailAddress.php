<?php

namespace eagle\modules\amazoncs\models;

use Yii;

/**
 * This is the model class for table "cs_seller_email_address".
 *
 * @property integer $id
 * @property string $email_address
 * @property string $platform
 * @property string $seller_id
 * @property string $site_id
 * @property string $create_time
 * @property string $update_time
 * @property string $status
 * @property integer $sent_count
 * @property string $addi_info
 */
class CsSellerEmailAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_seller_email_address';
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
            [['email_address', 'seller_id', 'site_id'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['sent_count'], 'integer'],
            [['addi_info'], 'string'],
            [['email_address', 'seller_id', 'site_id'], 'string', 'max' => 255],
            [['platform', 'status'], 'string', 'max' => 100],
            [['platform', 'seller_id', 'site_id'], 'unique', 'targetAttribute' => ['platform', 'seller_id', 'site_id'], 'message' => 'The combination of Platform, Seller ID and Site ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email_address' => 'Email Address',
            'platform' => 'Platform',
            'seller_id' => 'Seller ID',
            'site_id' => 'Site ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'sent_count' => 'Sent Count',
            'addi_info' => 'Addi Info',
        ];
    }
}
