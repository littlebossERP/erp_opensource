<?php

namespace eagle\modules\amazon\models;

use Yii;

/**
 * This is the model class for table "amazon_review_info".
 *
 * @property integer $review_id
 * @property string $merchant_id
 * @property string $marketplace_id
 * @property string $asin
 * @property string $create_time
 * @property string $rating
 * @property string $title
 * @property string $author
 * @property string $format_strip
 * @property string $verified_purchase
 * @property string $review_comments
 */
class AmazonReviewInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_review_info';
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
            [['merchant_id', 'marketplace_id', 'asin', 'rating', 'title', 'author', 'verified_purchase'], 'required'],
            [['create_time'], 'integer'],
            [['rating'], 'number'],
            [['review_comments'], 'string'],
            [['merchant_id', 'marketplace_id', 'asin', 'author'], 'string', 'max' => 50],
            [['title', 'format_strip'], 'string', 'max' => 255],
            [['verified_purchase'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'review_id' => 'Review ID',
            'merchant_id' => 'Merchant ID',
            'marketplace_id' => 'Marketplace ID',
            'asin' => 'Asin',
            'create_time' => 'Create Time',
            'rating' => 'Rating',
            'title' => 'Title',
            'author' => 'Author',
            'format_strip' => 'Format Strip',
            'verified_purchase' => 'Verified Purchase',
            'review_comments' => 'Review Comments',
        ];
    }
}
