<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%queue_aliexpress_praise}}".
 *
 * @property string $id
 * @property integer $orderId
 * @property integer $score
 * @property string $feedbackContent
 */
class QueueAliexpressPraise extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_praise';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderId', 'status','sellerloginid'], 'required'],
            [['score'], 'integer'],
            [['feedbackContent'], 'string'],
			[['orderId'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderId' => 'Order ID',
            'score' => 'Score',
            'feedbackContent' => 'Feedback Content',
            'sellerloginid' => 'Sellerloginid',
            'status' => 'Status',
        ];
    }
}
