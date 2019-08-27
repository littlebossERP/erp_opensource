<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%ensogo_wish_tag_log}}".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $store_name
 * @property string $tags_info
 * @property integer $validity_period
 * @property integer $create_time
 * @property integer $update_time
 */
class EnsogoWishTagLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ensogo_wish_tag_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'validity_period', 'create_time', 'update_time'], 'integer'],
            [['tags_info'], 'required'],
            [['tags_info'], 'string'],
            [['store_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'store_name' => 'Store Name',
            'tags_info' => 'Tags Info',
            'validity_period' => 'Validity Period',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
