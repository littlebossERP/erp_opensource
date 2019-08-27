<?php

namespace eagle\models\db_queue2;

use Yii;

/**
 * This is the model class for table "ebay_item_photo_queue".
 *
 * @property integer $id
 * @property string $itemid
 * @property string $product_attributes
 * @property string $status
 * @property string $photo_url
 * @property string $create_time
 * @property string $success_time
 * @property string $expire_time
 * @property integer $retry_count
 * @property integer $puid
 */
class EbayItemPhotoQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_item_photo_queue';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['itemid', 'create_time', 'puid'], 'required'],
            [['photo_url'], 'string'],
            [['create_time', 'success_time', 'expire_time'], 'safe'],
            [['retry_count', 'puid'], 'integer'],
            [['itemid', 'product_attributes'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'itemid' => 'Itemid',
            'product_attributes' => 'Product Attributes',
            'status' => 'Status',
            'photo_url' => 'Photo Url',
            'create_time' => 'Create Time',
            'success_time' => 'Success Time',
            'expire_time' => 'Expire Time',
            'retry_count' => 'Retry Count',
            'puid' => 'Puid',
        ];
    }
}
