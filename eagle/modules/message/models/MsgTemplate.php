<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_msg_template".
 *
 * @property integer $id
 * @property string $name
 * @property string $subject
 * @property string $body
 * @property string $addi_info
 */
class MsgTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_msg_template';
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
            [['name', 'subject'], 'required'],
            [['subject', 'body'], 'string'],
            [['name', 'addi_info'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'subject' => 'Subject',
            'body' => 'Body',
            'addi_info' => 'Addi Info',
        ];
    }
}
