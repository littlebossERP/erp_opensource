<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_newegg_user".
 *
 * @property string $site_id
 * @property string $store_name
 * @property integer $uid
 * @property string $SellerID
 * @property string $Authorization
 * @property string $SecretKey
 * @property integer $is_active
 * @property string $create_time
 * @property string $update_time
 * @property integer $status
 */
class SaasNeweggUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_newegg_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'SellerID', 'Authorization', 'SecretKey', 'create_time'], 'required'],
            [['uid', 'is_active', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['store_name'], 'string', 'max' => 50],
            [['SellerID', 'Authorization', 'SecretKey'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'site_id' => 'Site ID',
            'store_name' => 'Store Name',
            'uid' => 'Uid',
            'SellerID' => 'Seller ID',
            'Authorization' => 'Authorization',
            'SecretKey' => 'Secret Key',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
        ];
    }
}
