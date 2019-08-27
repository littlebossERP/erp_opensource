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
class QueueAliexpressPraiseInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_praise_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderId', 'success','sellerloginid'], 'required'],
            [['score','errorCode'], 'integer'],
            [['feedbackContent','errorMessage'], 'string'],
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
            'errorCode' => 'Error Code',
            'errorMessage' => 'Error Message',
            'success' => 'Success',
        ];
    }
}
