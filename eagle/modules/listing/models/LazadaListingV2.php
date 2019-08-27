<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "lazada_listing_v2".
 *
 * @property integer $id
 * @property string $platform
 * @property string $site
 * @property integer $lazada_uid
 * @property string $group_id
 * @property string $SellerSku
 * @property string $name
 * @property integer $PrimaryCategory
 * @property string $Attributes
 * @property string $Skus
 * @property string $sub_status
 * @property integer $lb_status
 * @property string $error_message
 * @property string $operation_log
 * @property integer $create_time
 * @property integer $update_time
 */
class LazadaListingV2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_listing_v2';
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
            [['platform', 'site', 'lazada_uid', 'group_id', 'SellerSku', 'PrimaryCategory', 'create_time', 'update_time'], 'required'],
            [['lazada_uid', 'PrimaryCategory', 'lb_status', 'create_time', 'update_time'], 'integer'],
            [['Attributes', 'Skus', 'sub_status', 'error_message', 'operation_log'], 'string'],
            [['platform'], 'string', 'max' => 20],
            [['site'], 'string', 'max' => 10],
            [['group_id', 'SellerSku', 'name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'site' => 'Site',
            'lazada_uid' => 'Lazada Uid',
            'group_id' => 'Group ID',
            'SellerSku' => 'Seller Sku',
            'name' => 'Name',
            'PrimaryCategory' => 'Primary Category',
            'Attributes' => 'Attributes',
            'Skus' => 'Skus',
            'sub_status' => 'Sub Status',
            'lb_status' => 'Lb Status',
            'error_message' => 'Error Message',
            'operation_log' => 'Operation Log',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
