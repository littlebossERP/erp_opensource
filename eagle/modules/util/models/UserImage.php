<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_user_image".
 *
 * @property integer $id
 * @property string $origin_url
 * @property string $thumbnail_url
 * @property string $amazon_key
 * @property integer $origin_size
 * @property integer $thumbnail_size
 * @property string $create_time
 * @property integer $service
 * @property string $memo
 * @property string $original_name
 * @property integer $original_width
 * @property integer $original_height
 */
class UserImage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_user_image';
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
            [['origin_url', 'thumbnail_url', 'amazon_key', 'origin_size', 'thumbnail_size', 'create_time'], 'required'],
            [['origin_size', 'thumbnail_size', 'service', 'original_width', 'original_height'], 'integer'],
            [['create_time'], 'safe'],
            [['origin_url'], 'string', 'max' => 90],
            [['thumbnail_url'], 'string', 'max' => 100],
            [['amazon_key', 'original_name'], 'string', 'max' => 60],
            [['memo'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'origin_url' => 'Origin Url',
            'thumbnail_url' => 'Thumbnail Url',
            'amazon_key' => 'Amazon Key',
            'origin_size' => 'Origin Size',
            'thumbnail_size' => 'Thumbnail Size',
            'create_time' => 'Create Time',
            'service' => 'Service',
            'memo' => 'Memo',
            'original_name' => 'Original Name',
            'original_width' => 'Original Width',
            'original_height' => 'Original Height',
        ];
    }
}
