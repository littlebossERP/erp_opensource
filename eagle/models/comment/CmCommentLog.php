<?php

namespace eagle\models\comment;

use Yii;

/**
 * This is the model class for table "cm_comment_log".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $order_source_order_id
 * @property string $selleruserid
 * @property string $platform
 * @property string $source_buyer_user_id
 * @property string $subtotal
 * @property string $currency
 * @property integer $paid_time
 * @property string $content
 * @property integer $is_success
 * @property string $error_msg
 * @property integer $createtime
 * @property integer $rule_id
 * @property integer $order_source_create_time
 */
class CmCommentLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_comment_log';
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
            [['order_id', 'order_source_order_id', 'selleruserid', 'platform'], 'required'],
            [['order_id', 'paid_time', 'is_success', 'createtime', 'rule_id', 'order_source_create_time'], 'integer'],
            [['subtotal'], 'number'],
            [['order_source_order_id', 'selleruserid', 'source_buyer_user_id'], 'string', 'max' => 50],
            [['platform'], 'string', 'max' => 30],
            [['currency'], 'string', 'max' => 3],
            [['content'], 'string', 'max' => 1000],
            [['error_msg'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'order_source_order_id' => 'Order Source Order ID',
            'selleruserid' => 'Selleruserid',
            'platform' => 'Platform',
            'source_buyer_user_id' => 'Source Buyer User ID',
            'subtotal' => 'Subtotal',
            'currency' => 'Currency',
            'paid_time' => 'Paid Time',
            'content' => 'Content',
            'is_success' => 'Is Success',
            'error_msg' => 'Error Msg',
            'createtime' => 'Createtime',
            'rule_id' => 'Rule ID',
            'order_source_create_time' => 'Order Source Create Time',
        ];
    }
}
