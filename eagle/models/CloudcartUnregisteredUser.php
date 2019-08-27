<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cloudcart_unregistered_user".
 *
 * @property string $id
 * @property string $email
 * @property integer $create_date
 * @property string $nickname
 * @property string $qq
 * @property string $cellphone
 * @property string $telephone
 * @property integer $puid
 * @property integer $tag_id
 */
class CloudcartUnregisteredUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cloudcart_unregistered_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_date', 'puid', 'tag_id'], 'integer'],
            [['email', 'nickname'], 'string', 'max' => 50],
            [['qq', 'cellphone', 'telephone'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'create_date' => 'Create Date',
            'nickname' => 'Nickname',
            'qq' => 'Qq',
            'cellphone' => 'Cellphone',
            'telephone' => 'Telephone',
            'puid' => 'Puid',
            'tag_id' => 'Tag ID',
        ];
    }
}
