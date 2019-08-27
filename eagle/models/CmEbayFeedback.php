<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cm_ebay_feedback".
 *
 * @property string $id
 * @property string $feedback_id
 * @property string $ebay_uid
 * @property string $selleruserid
 * @property string $transaction_id
 * @property string $od_ebay_transaction_id
 * @property string $itemid
 * @property string $role
 * @property string $commenting_user
 * @property string $commenting_user_score
 * @property string $comment_time
 * @property string $comment_text
 * @property string $comment_type
 * @property string $feedback_score
 * @property string $feedback_response
 * @property string $followup
 * @property integer $response_replaced
 * @property integer $has_read
 * @property string $create_time
 * @property string $update_time
 */
class CmEbayFeedback extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_feedback';
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
            [['feedback_id', 'ebay_uid', 'od_ebay_transaction_id', 'itemid', 'commenting_user_score', 'comment_time', 'feedback_score', 'response_replaced', 'has_read', 'create_time', 'update_time'], 'integer'],
            [['commenting_user_score', 'comment_text'], 'required'],
            [['comment_text'], 'string'],
            [['selleruserid', 'commenting_user'], 'string', 'max' => 50],
            [['transaction_id'], 'string', 'max' => 14],
            [['role'], 'string', 'max' => 10],
            [['comment_type'], 'string', 'max' => 32],
            [['feedback_response', 'followup'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'feedback_id' => 'Feedback ID',
            'ebay_uid' => 'Ebay Uid',
            'selleruserid' => 'Selleruserid',
            'transaction_id' => 'Transaction ID',
            'od_ebay_transaction_id' => 'Od Ebay Transaction ID',
            'itemid' => 'Itemid',
            'role' => 'Role',
            'commenting_user' => 'Commenting User',
            'commenting_user_score' => 'Commenting User Score',
            'comment_time' => 'Comment Time',
            'comment_text' => 'Comment Text',
            'comment_type' => 'Comment Type',
            'feedback_score' => 'Feedback Score',
            'feedback_response' => 'Feedback Response',
            'followup' => 'Followup',
            'response_replaced' => 'Response Replaced',
            'has_read' => 'Has Read',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
