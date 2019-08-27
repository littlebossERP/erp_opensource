<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_info".
 *
 * @property string $uid
 * @property string $familyname
 * @property string $company
 * @property string $address
 * @property string $cellphone
 * @property string $telephone
 * @property string $qq
 * @property string $remark
 */
class UserInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid'], 'required'],
            [['uid'], 'integer'],
            [['remark'], 'string'],
            [['familyname', 'company', 'cellphone', 'telephone', 'qq'], 'string', 'max' => 45],
            [['address'], 'string', 'max' => 100],
            [['uid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'familyname' => 'Familyname',
            'company' => 'Company',
            'address' => 'Address',
            'cellphone' => 'Cellphone',
            'telephone' => 'Telephone',
            'qq' => 'Qq',
            'remark' => 'Remark',
        ];
    }
}
