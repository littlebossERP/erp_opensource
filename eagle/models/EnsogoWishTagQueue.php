<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%ensogo_wish_tag_queue}}".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $platform
 * @property integer $create_time
 * @property integer $update_time
 */
class EnsogoWishTagQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ensogo_wish_tag_queue}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'create_time', 'update_time'], 'integer'],
            [['platform'], 'required'],
            [['platform'], 'string']
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
            'platform' => 'Platform',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
