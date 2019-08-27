<?php

namespace eagle\modules\amazoncs\models;

use Yii;

/**
 * This is the model class for table "amazon_feedback_info".
 *
 * @property integer $feedback_id
 * @property string $create_time
 * @property integer $rating
 * @property string $feedback_comments
 * @property integer $arrived_on_time
 * @property integer $item_as_described
 * @property integer $customer_service
 * @property string $order_source_order_id
 * @property string $rater_email
 * @property string $rater_role
 * @property string $respond_url
 * @property string $resolve_url
 * @property string $message_from_amazon
 * @property integer $rating_status
 * @property string $marketplace_id
 */
class AmazonFeedbackInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_feedback_info';
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
            [['create_time', 'rating', 'arrived_on_time', 'item_as_described', 'customer_service', 'rating_status'], 'integer'],
            [['feedback_comments', 'order_source_order_id', 'rater_email', 'marketplace_id'], 'required'],
            [['feedback_comments', 'message_from_amazon'], 'string'],
            [['order_source_order_id', 'rater_email'], 'string', 'max' => 100],
            [['rater_role'], 'string', 'max' => 10],
            [['respond_url', 'resolve_url'], 'string', 'max' => 250],
            [['marketplace_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'feedback_id' => 'Feedback ID',
            'create_time' => 'Create Time',
            'rating' => 'Rating',
            'feedback_comments' => 'Feedback Comments',
            'arrived_on_time' => 'Arrived On Time',
            'item_as_described' => 'Item As Described',
            'customer_service' => 'Customer Service',
            'order_source_order_id' => 'Order Source Order ID',
            'rater_email' => 'Rater Email',
            'rater_role' => 'Rater Role',
            'respond_url' => 'Respond Url',
            'resolve_url' => 'Resolve Url',
            'message_from_amazon' => 'Message From Amazon',
            'rating_status' => 'Rating Status',
            'marketplace_id' => 'Marketplace ID',
        ];
    }
}
