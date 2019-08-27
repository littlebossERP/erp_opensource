<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cloudcart_user_contact_record".
 *
 * @property string $id
 * @property string $type
 * @property integer $create_time
 * @property integer $next_time
 * @property string $title
 * @property string $memo
 * @property integer $puid
 * @property integer $unregistered_id
 */
class CloudcartUserContactRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cloudcart_user_contact_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time', 'next_time', 'puid', 'unregistered_id'], 'integer'],
            [['memo'], 'string'],
            [['type'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'create_time' => 'Create Time',
            'next_time' => 'Next Time',
            'title' => 'Title',
            'memo' => 'Memo',
            'puid' => 'Puid',
            'unregistered_id' => 'Unregistered ID',
        ];
    }
}
