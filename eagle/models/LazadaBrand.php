<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_brand".
 *
 * @property integer $id
 * @property string $site
 * @property string $Name
 * @property string $GlobalIdentifier
 */
class LazadaBrand extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_brand';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['site', 'Name', 'GlobalIdentifier'], 'required'],
            [['site'], 'string', 'max' => 10],
            [['Name', 'GlobalIdentifier'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'site' => 'Site',
            'Name' => 'Name',
            'GlobalIdentifier' => 'Global Identifier',
        ];
    }
}
