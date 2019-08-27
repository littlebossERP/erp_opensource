<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "priceminister_sync_order_item".
 *
 * @property integer $id
 * @property integer $item_id
 * @property integer $puid
 * @property string $seller_id
 * @property string $purchase_id
 * @property string $status
 * @property string $item_status
 * @property string $result
 * @property integer $times
 * @property string $create
 * @property string $update
 * @property string $err_message
 * @property string $addi_info
 */
class PriceministerSyncOrderItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'priceminister_sync_order_item';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['item_id', 'seller_id', 'purchase_id', 'status', 'item_status'], 'required'],
            [['item_id', 'puid', 'times'], 'integer'],
            [['result', 'err_message', 'addi_info'], 'string'],
            [['create', 'update'], 'safe'],
            [['seller_id', 'purchase_id', 'item_status'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_id' => 'Item ID',
            'puid' => 'Puid',
            'seller_id' => 'Seller ID',
            'purchase_id' => 'Purchase ID',
            'status' => 'Status',
            'item_status' => 'Item Status',
            'result' => 'Result',
            'times' => 'Times',
            'create' => 'Create',
            'update' => 'Update',
            'err_message' => 'Err Message',
            'addi_info' => 'Addi Info',
        ];
    }
}
