<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cm_ebay_usercase".
 *
 * @property integer $id
 * @property integer $uid
 * @property integer $mytype
 * @property string $selleruserid
 * @property string $buyeruserid
 * @property string $caseid
 * @property string $type
 * @property string $status_type
 * @property string $status_value
 * @property string $itemid
 * @property string $itemtitle
 * @property string $transactionid
 * @property integer $casequantity
 * @property string $caseamount
 * @property integer $created_date
 * @property integer $lastmodified_date
 * @property integer $respondbydate
 * @property integer $has_read
 * @property integer $order_id
 * @property integer $create_time
 * @property integer $update_time
 */
class CmEbayUsercase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_usercase';
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
            [['uid', 'itemtitle', 'caseamount', 'created_date', 'lastmodified_date', 'respondbydate', 'order_id', 'create_time', 'update_time'], 'required'],
            [['uid', 'mytype', 'itemid', 'casequantity', 'created_date', 'lastmodified_date', 'respondbydate', 'has_read', 'order_id', 'create_time', 'update_time'], 'integer'],
            [['selleruserid'], 'string', 'max' => 50],
            [['buyeruserid', 'type', 'status_type', 'status_value'], 'string', 'max' => 100],
            [['caseid'], 'string', 'max' => 40],
            [['itemtitle'], 'string', 'max' => 255],
            [['transactionid'], 'string', 'max' => 14],
            [['caseamount'], 'string', 'max' => 15],
            [['caseid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'mytype' => 'Mytype',
            'selleruserid' => 'Selleruserid',
            'buyeruserid' => 'Buyeruserid',
            'caseid' => 'Caseid',
            'type' => 'Type',
            'status_type' => 'Status Type',
            'status_value' => 'Status Value',
            'itemid' => 'Itemid',
            'itemtitle' => 'Itemtitle',
            'transactionid' => 'Transactionid',
            'casequantity' => 'Casequantity',
            'caseamount' => 'Caseamount',
            'created_date' => 'Created Date',
            'lastmodified_date' => 'Lastmodified Date',
            'respondbydate' => 'Respondbydate',
            'has_read' => 'Has Read',
            'order_id' => 'Order ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
