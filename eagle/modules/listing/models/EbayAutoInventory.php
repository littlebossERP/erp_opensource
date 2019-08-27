<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_auto_inventory".
 *
 * @property string $id
 * @property string $selleruserid
 * @property string $draft_id
 * @property string $itemid
 * @property string $sku
 * @property integer $item_type
 * @property string $var_specifics
 * @property integer $online_quantity
 * @property integer $status
 * @property integer $status_process
 * @property string $type
 * @property integer $less_than_equal_to
 * @property integer $inventory
 * @property integer $success_cnt
 * @property integer $err_cnt
 * @property integer $ebay_uid
 * @property string $puid
 * @property integer $created
 * @property integer $updated
 */
class EbayAutoInventory extends \yii\db\ActiveRecord
{
    //状态机
    const CHECK_PENDING=0;
    const CHECK_RUNNING=1;
    const CHECK_EXECEPT=3;
    const CHECK_NOITEM=4;
    const INV_PENDING=2;
    const INV_RUNNING=10;
    const INV_FINISH=20;
    const INV_EXECEPT=30;
    //错误码
    const CODE_SUCCESS =1;
    const CODE_ERR_API = 3;
    const CODE_ERR_DB = 4;
    //状态
    const STATUS_SUSPEND=0;
    const STATUS_OPEND=1;
    const STATUS_CLOSED=1;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_auto_inventory';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['draft_id', 'itemid', 'item_type', 'online_quantity', 'status', 'status_process', 'less_than_equal_to', 'inventory', 'success_cnt', 'err_cnt', 'ebay_uid', 'puid', 'created', 'updated'], 'integer'],
            [['itemid'], 'required'],
            [['var_specifics'], 'string'],
            [['selleruserid'], 'string', 'max' => 50],
            [['sku'], 'string', 'max' => 55],
            [['type'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'selleruserid' => 'Selleruserid',
            'draft_id' => 'Draft ID',
            'itemid' => 'Itemid',
            'sku' => 'Sku',
            'item_type' => 'Item Type',
            'var_specifics' => 'Var Specifics',
            'online_quantity' => 'Online Quantity',
            'status' => 'Status',
            'status_process' => 'Status Process',
            'type' => 'Type',
            'less_than_equal_to' => 'Less Than Equal To',
            'inventory' => 'Inventory',
            'success_cnt' => 'Success Cnt',
            'err_cnt' => 'Err Cnt',
            'ebay_uid' => 'Ebay Uid',
            'puid' => 'Puid',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
